<?php
use RdsSystem\Message;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=AsyncReader_Merge -vv
 */
class Cronjob_Tool_AsyncReader_Merge extends RdsSystem\Cron\RabbitDaemon
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return array() + parent::getCommandLineSpec();
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model  = $this->getMessagingModel($cronJob);

        $model->readMergeTaskResult(false, function(Message\Merge\TaskResult $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received merge result: ".json_encode($message));
            $this->actionProcessMergeResult($message, $model);
        });

        $model->readDroppedBranches(false, function(Message\Merge\DroppedBranches $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received merge result: ".json_encode($message));
            $this->actionProcessDroppedBranches($message, $model);
        });

        $this->debugLogger->message("Start listening");

        $this->waitForMessages($model, $cronJob);
    }

    private function actionProcessDroppedBranches(Message\Merge\DroppedBranches $message, MessagingRdsMs $model)
    {
        $this->debugLogger->message("Received removed branches: branch=$message->branch, skippedRepositories: ".json_encode($message->skippedRepositories));
        $c = new CDbCriteria();
        $c->compare("jf_branch", $message->branch);
        $c->compare('jf_status', JiraFeature::STATUS_REMOVING);

        $update = [
            'jf_blocker_commits' => json_encode($message->skippedRepositories, JSON_PRETTY_PRINT),
        ];

        if (!$message->skippedRepositories) {
            $update['jf_status'] = JiraFeature::STATUS_REMOVED;
        }

        JiraFeature::model()->updateAll($update, $c);

        $message->accepted();
        $this->debugLogger->message("Message accepted");
    }

    public function actionProcessMergeResult(Message\Merge\TaskResult $message, MessagingRdsMs $model)
    {
        $transitionMap = [
            "develop" => \Jira\Transition::MERGED_TO_DEVELOP,
            "master" => \Jira\Transition::MERGED_TO_MASTER,
            "staging" => \Jira\Transition::MERGED_TO_STAGING,
        ];

        /** @var $feature JiraFeature */
        $feature = JiraFeature::model()->findByPk($message->featureId);
        if (!$feature) {
            $this->debugLogger->error("Feature #$message->featureId not found");
            $message->accepted();
            return;
        }

        $jira = new JiraApi($this->debugLogger);
        $ticketInfo = $jira->getTicketInfo($feature->jf_ticket);

        if (isset($transitionMap[$message->targetBranch])) {
            if ($message->status) {
                //an: Если мерж прошел успешно - двигаем задачу в след. статус
                $this->debugLogger->message("Branch was merged successful, transition ticket to next status");
                $transition = $transitionMap[$message->targetBranch];
                $jira->transitionTicket($ticketInfo, $transition, "Задача была успешно слита в ветку $message->targetBranch", true);
            } else {
                //an: Если не смержилась - просто пишем комент с ошибками мержа
                $this->debugLogger->message("Branch was merged fail, sending comment");
                $mergeFix = $message->targetBranch == 'master' ? "$message->targetBranch в $message->sourceBranch" : "$message->sourceBranch в $message->targetBranch";
                $text = "Случились ошибки при мерже ветки $message->sourceBranch в $message->targetBranch. Разрешите эти ошибки путем мержа $mergeFix:\n".implode("\n", $message->errors);
                if ($jira->addCommentOrModifyMyComment($feature->jf_ticket, $text)) {

                    $text = str_replace("\r", "", $text);
                    $text = preg_replace('~^h\d\.(.*)~', '<b>$1</b>', $text);
                    $text = preg_replace('~\nh\d\.(.*)~', "\n<b>$1</b>", $text);
                    $text = preg_replace('~{quote}(.*?){quote}~sui', '<pre>$1</pre>', $text);

                    Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendRdsConflictNotification'}(
                        $feature->developer->finam_email,
                        strtolower(preg_replace('~(\w+)\-\d+~', '$1', $feature->jf_ticket)).'@whotrades.org',
                        'Merge conflict at '.$feature->jf_ticket,
                        $text
                    );
                }
            }

            //an: И в любом случае отправляем задачу обратно разработчику (если не смержилась - пусто мержит, если смержилась - пусть дальше работает:) )
            if ($lastDeveloper = $jira->getLastDeveloperNotRds($ticketInfo)) {
                $jira->assign($feature->jf_ticket, $lastDeveloper);
            }
        } else {
            $this->debugLogger->error("Unknown target branch, skip jira integration");
        }
        $message->accepted();
    }
}
