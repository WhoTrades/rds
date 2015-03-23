<?php

class JiraJsonController extends Controller
{
    const MAX_FEATURES_PER_DEVELOPER = 15;
    /** @var JiraApi */
    private $jiraApi;

    public function init()
    {
        $this->jiraApi = new JiraApi(Yii::app()->debugLogger);
    }

    public function actionGetTicketInfo($ticket)
    {
        try {
            $data = $this->jiraApi->getTicketInfo($ticket);
        } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            header("HTTP/1.0 {$e->getHttpCode()}");
            echo $e->getResponse();
            return;
        }

        $this->printJson($data);
    }

    public function actionStartTicketWork($ticket, $user)
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'messages' => [],
        ];
        try {
            $data = $this->jiraApi->getTicketInfo($ticket);

            /** @var $developer Developer */
            $developer = Developer::getByWhoTradesEmail($user);
            if ($developer) {
                $nonClosedFeatures = JiraFeature::model()->getNonClosedJiraFeatures($developer->obj_id, $ticket);
                if (count($nonClosedFeatures) >= self::MAX_FEATURES_PER_DEVELOPER) {
                    $list = [];
                    foreach ($nonClosedFeatures as $feature) {
                        $list[] = $feature->jf_ticket;
                    }
                    sort($list);
                    $result['errors'][] = "You have to many not closed features, deploy them to prod first: ".implode(", ", $list);
                } else {
                    if ($data['fields']['assignee']['emailAddress'] == null) {
                        //an: если задача ничья - переводим на разработчика, что начал работу над ней
                        $this->jiraApi->assign($ticket, $developer->finam_email);
                        $result['messages'][] = "Task was assigned to you because there was no developer";
                    } else if ($data['fields']['assignee']['emailAddress'] != $developer->finam_email) {
                        //an: если задача принадлеит другому разработчику - ругаемся в лог и пишем в жиру комментарий
                        $result['warnings'][] = "Start of work for alien task (you are $developer->finam_email, but task developer is {$data['fields']['assignee']['emailAddress']})";
                        $this->jiraApi->addComment($ticket, "Разработчик [~".JiraApi::getUserNameByEmail($developer->finam_email)."] начал работу над этой задачей, хотя не является её исполнителем");
                    }
                }
            } else {
                $result['errors'][] = "Unknown user $user, please register yourself at  RDS http://rds.whotrades.com/developer/create";
            }

            if (empty($result['errors'])) {
                if ($data['fields']['status']['name'] == \Jira\Status::STATUS_READY_FOR_DEVELOPMENT) {
                    $result['messages'][] = 'Ticket '.$ticket.' moved to status "In progress"';
                    $this->jiraApi->transitionTicket($data, \Jira\Transition::START_PROGRESS);
                } elseif ($data['fields']['status']['name'] != \Jira\Status::STATUS_IN_PROGRESS) {
                    $result['errors'][] = 'Cant start work for ticket '.$ticket.' as it\'s status in Jira not "In progress" or "Ready for development"';
                }
                if (empty($result['errors'])) {
                    $branch = "feature/$ticket";

                    /** @var $existingFeature JiraFeature */
                    $existingFeature = JiraFeature::model()->findByAttributes([
                        'jf_developer_id'   => $developer->obj_id,
                        'jf_ticket'         => $ticket
                    ]);
                    if ($existingFeature) {
                        $existingFeature->jf_status = JiraFeature::STATUS_IN_PROGRESS;
                        $existingFeature->resetMergeConditions();
                        $existingFeature->save();

                        $result['branch'] = $existingFeature->jf_branch;
                    } else {
                        $feature = new JiraFeature();
                        $feature->attributes = [
                            'jf_developer_id' => $developer->obj_id,
                            'jf_ticket' => $ticket,
                            'jf_branch' => $branch,
                        ];
                        $feature->save();
                    }
                }
            }
        } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            $result['errors'][] = 'Ticket not found, '.$e->getResponse();
        }
        $this->printJson($result);
    }

    public function actionFinishTicketWork($ticket, $user)
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'messages' => [],
        ];
        try {
            $data = $this->jiraApi->getTicketInfo($ticket);

            //an: Проверяем что разработчик зарегистрирован и ругаетмся в комментарии, если разработчик не является исполнителем этого тикета
            /** @var $developer Developer */
            $developer = Developer::getByWhoTradesEmail($user);
            if ($developer) {
                if ($data['fields']['assignee']['emailAddress'] == null) {
                    //an: если задача ничья - переводим на разработчика, что начал работу над ней
                    $this->jiraApi->assign($ticket, $user);
                    $result['messages'][] = "Task was assigned to you because there was no developer";
                } else if ($data['fields']['assignee']['emailAddress'] != $developer->finam_email) {
                    //an: если задача принадлеит другому разработчику - ругаемся в лог и пишем в жиру комментарий
                    $result['warnings'][] = "Finish of work for alien task (you are $developer->finam_email, but task developer is {$data['fields']['assignee']['emailAddress']})";
                    $this->jiraApi->addComment($ticket, "[!] Разработчик [~".JiraApi::getUserNameByEmail($developer->finam_email)."] завершил работу над этой задачей, хотя не является её исполнителем");
                }
            } else {
                $result['errors'][] = "Unknown user $user, please register yourself at  RDS http://rds.whotrades.com/developer/create";
            }

            /** @var $existingFeature JiraFeature */
            $existingFeature = JiraFeature::model()->findByAttributes([
                'jf_developer_id'   => $developer->obj_id,
                'jf_ticket'         => $ticket
            ]);

            if (!in_array($data['fields']['status']['name'], [\Jira\Status::STATUS_READY_FOR_DEVELOPMENT, \Jira\Status::STATUS_IN_PROGRESS])) {
                $result['errors'][] = "Ticket status is \"{$data['fields']['status']['name']}\", but only 'In progress' or 'ready for development' is allowed to finish job";
            }

            //an: Проверяем что фича существует и в правильном статусе
            if (!$existingFeature) {
                $result['errors'][] = "Unknown feature $ticket, please run `php wtflow.php start $ticket` first";
            } elseif ($existingFeature->jf_status != JiraFeature::STATUS_IN_PROGRESS && $existingFeature->jf_status != JiraFeature::STATUS_PAUSED) {
                if ($existingFeature->jf_status == JiraFeature::STATUS_CHECKING) {
                    if (in_array($data['fields']['status']['name'], [\Jira\Status::STATUS_IN_PROGRESS, \Jira\Status::STATUS_READY_FOR_DEVELOPMENT])) {
                        //an: Задачу вернули на доработку, обновляем статус у себя
                        $existingFeature->jf_status = JiraFeature::STATUS_IN_PROGRESS;
                        $existingFeature->save();
                    } else {
                        //an: Задача в непонятнойм статусе
                        $result['errors'][] = "Feature $ticket was already finished, for all new changes use new ticket";
                    }
                } elseif ($existingFeature->jf_status == JiraFeature::STATUS_CANCELLED) {
                    $result['errors'][] = "Feature $ticket was cancelled";
                } else {
                    $result['errors'][] = "Feature $ticket has illegal status '$existingFeature->jf_status'";
                }
            }

            if (empty($result['errors'])) {
                /** @var $existingFeature JiraFeature */
                //an: если статус тикета "готово к разработке" - то просто двигаем его сначала в "в работе", а потом и в "Continuous integration"
                if ($data['fields']['status']['name'] == \Jira\Status::STATUS_READY_FOR_DEVELOPMENT) {
                    $this->jiraApi->transitionTicket($data, \Jira\Transition::START_PROGRESS);
                    $data = $this->jiraApi->getTicketInfo($ticket);
                }
                if ($data['fields']['status']['name'] == \Jira\Status::STATUS_IN_PROGRESS) {
                    $this->jiraApi->transitionTicket($data, \Jira\Transition::FINISH_DEVELOPMENT, "Разработчик ".JiraApi::getUserNameByEmail($developer->finam_email)." завершил работу над этой задачей");
                    $result['messages'][] = 'Ticket '.$ticket.' moved to status "Continuous integration"';
                }

                $existingFeature->jf_status = JiraFeature::STATUS_CHECKING;
                $existingFeature->resetMergeConditions();
                $existingFeature->save();

                $task = new TeamcityRunTest();
                $task->attributes = [
                    'trt_jira_feature_obj_id' => $existingFeature->obj_id,
                    'trt_branch' => $existingFeature->jf_branch,
                ];
                $task->save();
            }
        } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            $result['errors'][] = 'Ticket not found, '.$e->getResponse();
        }

        $this->printJson($result);
    }

    /**
     * Webhook used AFTER (not before!) ticket transition from 'In Progress' in 'Continous Integration'
     *
     * @param $ticket
     * @throws ApplicationException
     */
    public function actionManualFinishTicketWork($ticket)
    {
        ob_get_clean();

        $result = [
            'errors' => [],
            'warnings' => [],
            'messages' => [],
        ];

        try {
            // vs : DON'T throw TicketException before $data was defined
            $data = $this->jiraApi->getTicketInfo($ticket);

            // vs: check is wtflow do ticket transition? if yes - do notihing
            if ($this->jiraApi->isStatusChangedByRds($data, 'In Progress')) {
                $result['messages'][] = 'Ticket ' . $ticket . ' already handled by wtflow';
            } else {
                // vs : $ticket MUST be in status 'Countinous integration' after native Jira transition
                if ($data['fields']['status']['name'] != \Jira\Status::STATUS_IN_CONTINUOUS_INTEGRATION) {
                    throw new TicketException(
                        "Ticket status is \"{$data['fields']['status']['name']}\", but only 'Continous integration' needed for manual finish!",
                        101
                    );
                }

                // vs: get assignee data from ticket
                $assignee = isset($data['fields']['assignee']) ? $data['fields']['assignee'] : array();

                if (!$assignee) {
                    throw new TicketException("No assignee defined in issue {$ticket} please, set assignee", 102);
                }

                /** @var $developer Developer */
                $developer = Developer::getByFinamEmail($assignee['emailAddress']);
                if (!$developer) {
                    throw new TicketException(
                        "Unknown user {$assignee['emailAddress']}, please register yourself at  RDS http://rds.whotrades.com/developer/create",
                        103
                    );
                }

                /** @var $existingFeature JiraFeature */
                $existingFeature = JiraFeature::model()->findByAttributes([
                    'jf_developer_id' => $developer->obj_id,
                    'jf_ticket' => $ticket
                ]);

                //an: Проверяем что фича существует и в правильном статусе
                if (!$existingFeature) {
                    // vs: lets start it in rds
                    $existingFeature = new JiraFeature();
                    $existingFeature->attributes = [
                        'jf_status' => JiraFeature::STATUS_IN_PROGRESS,
                        'jf_developer_id' => $developer->obj_id,
                        'jf_ticket' => $ticket,
                        'jf_branch' => "feature/$ticket",
                    ];
                    $existingFeature->save();
                }

                // vs : save into RDS database
                $existingFeature->jf_status = JiraFeature::STATUS_CHECKING;
                $existingFeature->resetMergeConditions();
                $existingFeature->save();

                $task = new TeamcityRunTest();
                $task->attributes = [
                    'trt_jira_feature_obj_id' => $existingFeature->obj_id,
                    'trt_branch' => $existingFeature->jf_branch,
                ];
                $task->save();
            }
        } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            $result['errors'][] = 'Ticket not found, '.$e->getResponse();
        } catch (TicketException $e) {
            $result['errors'][] = $e->getMessage();
            $this->jiraApi->transitionTicket(
                $data,
                \Jira\Transition::FAILED_INTEGRATION_TESTING
            );

            $comment = "Попытка завершить задачу {$ticket} вручную завершилась ошибкой: ".$e->getMessage();

            $this->jiraApi->addComment($ticket, $comment);
            mail($data['fields']['assignee']['emailAddress'], "{$ticket} : не удалось закрыть вручную", $comment);

            if (isset($developer) && $developer) {
                /** @var $existingFeature JiraFeature */
                $existingFeature = JiraFeature::model()->findByAttributes([
                    'jf_developer_id' => $developer->obj_id,
                    'jf_ticket' => $ticket
                ]);

                // vs : save into RDS database
                $existingFeature->jf_status = JiraFeature::STATUS_IN_PROGRESS;
                $existingFeature->resetMergeConditions();
                $existingFeature->save();
            }
        }

        $this->printJson($result);
    }

    public function printJson($data)
    {
        header("Content-type: text/javascript; charset=utf-8");

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
