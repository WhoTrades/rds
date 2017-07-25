<?php

use RdsSystem\Message;
use app\modules\Wtflow\models\GitBuild;
use app\modules\Wtflow\Jira\Transition;
use app\modules\Wtflow\components\JiraApi;
use app\modules\Wtflow\models\JiraFeature;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;
use app\modules\Wtflow\models\GitBuildBranch;

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
     * @param \Cronjob\ICronjob $cronJob
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model  = $this->getMessagingModel($cronJob);

        $workerName = \Config::getInstance()->workerName;

        $model->readMergeTaskResult($workerName, false, function (Message\Merge\TaskResult $message) use ($model) {
            Yii::info("env={$model->getEnv()}, Received merge result: " . json_encode($message));

            if ($message->type == Message\Merge\Task::MERGE_TYPE_BUILD) {
                $this->actionProcessMergeBuildResult($message, $model);
            } else {
                $this->actionProcessMergeFeatureResult($message, $model);
            }
        });

        $model->readDroppedBranches(false, function (Message\Merge\DroppedBranches $message) use ($model) {
            Yii::info("env={$model->getEnv()}, Received merge result: " . json_encode($message));
            $this->actionProcessDroppedBranches($message, $model);
        });

        Yii::info("Start listening");

        $this->waitForMessages($model, $cronJob);
    }

    private function actionProcessDroppedBranches(Message\Merge\DroppedBranches $message, MessagingRdsMs $model)
    {
        Yii::info("Received removed branches: branch=$message->branch, skippedRepositories: " . json_encode($message->skippedRepositories));
        $updateWhere    = ['jf_branch' => $message->branch, 'jf_status' => JiraFeature::STATUS_REMOVING];
        $updateFields   = ['jf_blocker_commits' => json_encode($message->skippedRepositories, JSON_PRETTY_PRINT)];

        if (!$message->skippedRepositories) {
            $update['jf_status'] = JiraFeature::STATUS_REMOVED;
        }

        JiraFeature::updateAll($updateFields, $updateWhere);
        $message->accepted();
        Yii::info("Message accepted");
    }

    /**
     * @param Message\Merge\TaskResult $message
     * @param MessagingRdsMs           $model
     */
    public function actionProcessMergeFeatureResult(Message\Merge\TaskResult $message, MessagingRdsMs $model)
    {
        $transitionMap = [
            "develop" => Transition::MERGED_TO_DEVELOP,
            "master" => Transition::MERGED_TO_MASTER,
            "staging" => Transition::MERGED_TO_STAGING,
        ];

        /** @var $feature JiraFeature */
        $feature = JiraFeature::findByPk($message->featureId);
        if (!$feature) {
            Yii::error("Feature #$message->featureId not found");
            $message->accepted();

            return;
        }

        $jira = \Yii::$app->getModule('Wtflow')->jira;
        try {
            $ticketInfo = $jira->getTicketInfo($feature->jf_ticket);
        } catch (\CompanyInfrastructure\Exception\Jira\TicketNotFound $e) {
            Yii::error("Skip accepting ticket $feature->jf_ticket, as it was deleted");
            $message->accepted();

            return;
        }

        if (isset($transitionMap[$message->targetBranch])) {
            if ($message->status) {
                // an: Если мерж прошел успешно - двигаем задачу в след. статус
                Yii::info("Branch was merged successful, transition ticket to next status");
                $transition = $transitionMap[$message->targetBranch];
                // ar: Сохраняем время мержа задачи в мастер
                if ($message->targetBranch === 'master') {
                    JiraFeature::updateAll([
                        'jf_merged_to_master_time' => date('Y-m-d H:i:s'),
                    ], ['jf_ticket' => $feature->jf_ticket]);
                }
                $jira->transitionTicket($ticketInfo, $transition, "Задача была успешно слита в ветку $message->targetBranch", true);
            } else {
                // an: Если не смержилась - просто пишем комент с ошибками мержа
                Yii::info("Branch was merged fail, sending comment");
                $mergeFix = $message->targetBranch == 'master' ? "$message->targetBranch в $message->sourceBranch" : "$message->sourceBranch в $message->targetBranch";
                $text = "Случились ошибки при мерже ветки $message->sourceBranch в $message->targetBranch. " .
                        "Разрешите эти ошибки путем мержа $mergeFix:\n" . implode("\n", $message->errors);

                if ($jira->addCommentOrModifyMyComment($feature->jf_ticket, $text)) {
                    $text = str_replace("\r", "", $text);
                    $text = preg_replace('~^h\d\.(.*)~', '<b>$1</b>', $text);
                    $text = preg_replace('~\nh\d\.(.*)~', "\n<b>$1</b>", $text);
                    $text = preg_replace('~{quote}(.*?){quote}~sui', '<pre>$1</pre>', $text);

                    Yii::$app->EmailNotifier->sendRdsConflictNotification(
                        $feature->developer->whotrades_email,
                        strtolower(preg_replace('~(\w+)\-\d+~', '$1', $feature->jf_ticket)) . '@whotrades.org',
                        $feature->jf_ticket,
                        $message->targetBranch,
                        $text
                    );
                }
            }

            JiraFeature::updateAll([
                'jf_last_merge_request_to_develop_time' => null,
                'jf_last_merge_request_to_staging_time' => null,
                'jf_last_merge_request_to_master_time' => null,
            ], "jf_ticket=:ticket", ['ticket' => $feature->jf_ticket]);

            // an: И если исполнитель все ещё RDS (могли руками переназначить) -  отправляем задачу обратно разработчику
            // (если не смержилась - пусто мержит, если смержилась - пусть дальше работает :) )
            if (($lastDeveloper = $jira->getLastDeveloperNotRds($ticketInfo)) && ($ticketInfo['fields']['assignee']['name'] == $jira->getUserName())) {
                try {
                    $jira->assign($feature->jf_ticket, $lastDeveloper);
                } catch (\Exception $e) {
                    Yii::error("Can't assign $lastDeveloper to $feature->jf_ticket");
                }
            }
        } else {
            Yii::error("Unknown target branch, skip jira integration");
        }
        $message->accepted();
    }

    /**
     * @param Message\Merge\TaskResult $message
     * @param MessagingRdsMs $model
     */
    public function actionProcessMergeBuildResult(Message\Merge\TaskResult $message, MessagingRdsMs $model)
    {
        /** @var $gitBuild GitBuild */
        $gitBuild = GitBuild::findByPk($message->featureId);
        if (!$gitBuild) {
            Yii::info("Can't find build $message->featureId, skip message");
            $message->accepted();

            return;
        }

        /** @var $gitBuildBranch GitBuildBranch */
        $gitBuildBranch = GitBuildBranch::findByAttributes([
            'git_build_id' => $gitBuild->obj_id,
            'branch' => $message->sourceBranch,
        ]);

        $gitBuildBranch->status = $message->status ? GitBuildBranch::STATUS_SUCCESS : GitBuildBranch::STATUS_ERROR;
        $gitBuildBranch->errors = implode("\n", $message->errors);
        $gitBuildBranch->save();


        if (0 == GitBuildBranch::countByAttributes([
            'git_build_id' => $gitBuild->obj_id,
            'status' => GitBuildBranch::STATUS_NEW,
        ])) {
            Yii::info("Build #$gitBuild->obj_id finished");
            // an: Если смержили (с ошибками или успешно) все ветки, но билд считаем завершенным

            $errorsCount = GitBuildBranch::countByAttributes([
                'git_build_id' => $gitBuild->obj_id,
                'status' => GitBuildBranch::STATUS_ERROR,
            ]);

            // an: Если собирали ради пересборки develop/staging и все успешно смержилось - тогда пушим пересоздаем ветки
            if (in_array($gitBuild->additional_data, ["develop", "staging"]) && $errorsCount == 0) {
                $model->sendMergeCreateBranch(
                    \Yii::$app->modules['Wtflow']->workerName,
                    new Message\Merge\CreateBranch($gitBuild->additional_data, $gitBuild->branch, true)
                );
                mail(
                    "dev-test-rebuilt-success@whotrades.org",
                    "[RDS] $gitBuild->additional_data успешно пересобрана",
                    "Все вмержилось, новый код можно смотреть на тестовом контуре"
                );
            } else {
                mail(
                    "dev-test-rebuilt-failed@whotrades.org",
                    "[RDS] Собрка ветки $gitBuild->additional_data завершилась неудачей",
                    "Часть задач не вмержились"
                );
            }

            $gitBuild->status = GitBuild::STATUS_FINISHED;
            $gitBuild->save();
        } else {
            Yii::info("Build #$gitBuild->obj_id NOT finished");
        }

        Yii::info("Message accepted");
        $message->accepted();

        self::updateGitBuildAtInterface($gitBuild);
    }

    /**
     * Функция отправляет по комету изменения GitBuild, что бы интерфейс мог отрисовать то что изменилось
     * @param GitBuild $gitBuild
     */
    private static function updateGitBuildAtInterface(GitBuild $gitBuild)
    {
        $model = app\modules\Wtflow\models\GitBuild::findByPk($gitBuild->obj_id);

        $html = \Yii::$app->view->renderFile('@app/modules/Wtflow/views/git-build/_gitBuildGrid.php', [
            'dataProvider' => $model->search(['obj_id' => $model->obj_id]),
        ]);

        Yii::$app->webSockets->send('gitBuildChanged', [
            'html' => $html,
            'git_build_id' => $gitBuild->obj_id,
        ]);
    }
}
