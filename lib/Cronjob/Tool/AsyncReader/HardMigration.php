<?php

use app\modules\Wtflow\components\JiraApi;
use yii\helpers\Url;
use RdsSystem\Message;
use app\modules\Wtflow\models\HardMigration;
use app\components\Jira\AsyncRpc;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;
use app\modules\Wtflow\models\JiraMoveTicket;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=AsyncReader_HardMigration -vv
 */
class Cronjob_Tool_AsyncReader_HardMigration extends RdsSystem\Cron\RabbitDaemon
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
     * @param \Cronjob\ICronjob $cronJob
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model  = $this->getMessagingModel($cronJob);

        $model->readHardMigrationStatus(false, function (Message\HardMigrationStatus $message) use ($model) {
            Yii::info("env={$model->getEnv()}, Received changing status of hard migration: " . json_encode($message));
            $this->actionUpdateHardMigrationStatus($message, $model);
        });

        Yii::info("Start listening");
        $this->waitForMessages($model, $cronJob);
    }


    public function actionUpdateHardMigrationStatus(Message\HardMigrationStatus $message, MessagingRdsMs $model)
    {
        /** @var $migration HardMigration */
        $migration = HardMigration::findByAttributes([
            'migration_name' => $message->migration,
            'migration_environment' => $model->getEnv(),
        ]);

        if (!$migration) {
            Yii::error("Can't find migration $message->migration, environment={$model->getEnv()}");
            $message->accepted();

            return;
        }


        //an: В жиру пишем только факт накатывания миграций на прод
        if ($model->getEnv() == 'main' && $migration->migration_status != $message->status) {
            if (\Config::getInstance()->serviceRds['jira']['repostMigrationStatus']) {
                /** @var $jira JiraApi */
                $jira = new AsyncRpc();

                switch ($message->status) {
                    case HardMigration::MIGRATION_STATUS_NEW:
                        //an: Это означает что миграцию пытались запустить, но миграция оказалась ещё не готова к запуску. Просто ничего не делаем
                        break;
                    case HardMigration::MIGRATION_STATUS_IN_PROGRESS:
                        $jira->addCommentOrAppendMyComment(
                            $migration->migration_ticket,
                            date("d.m.Y H:i") . ": " . "Запущена миграция $message->migration. Лог миграции: " .
                                $this->createUrl('/hardMigration/log', ['id' => $migration->obj_id])
                        );
                        break;
                    case HardMigration::MIGRATION_STATUS_DONE:
                        $jira->addCommentOrAppendMyComment(
                            $migration->migration_ticket,
                            date("d.m.Y H:i") . ": " . "Выполнена миграция $message->migration. Лог миграции: " .
                                $this->createUrl('/hardMigration/log', ['id' => $migration->obj_id])
                        );

                        $jiraMove = new JiraMoveTicket();
                        $jiraMove->attributes = [
                            'jira_ticket' => $migration->migration_ticket,
                            'jira_direction' => JiraMoveTicket::DIRECTION_UP,
                        ];

                        Yii::info("Adding ticket {$migration->migration_ticket} for moving up");

                        if (!$jiraMove->save()) {
                            Yii::error("Can't save JiraMoveTicket, errors: " . json_encode($jiraMove->errors));
                        }

                        break;
                    case HardMigration::MIGRATION_STATUS_FAILED:
                        $jira->addCommentOrAppendMyComment(
                            $migration->migration_ticket,
                            date("d.m.Y H:i") . ": " . "Завершилась с ошибкой миграция $message->migration. Лог миграции: " .
                                $this->createUrl('/hardMigration/log', ['id' => $migration->obj_id])
                        );
                        break;
                    default:
                        $jira->addCommentOrAppendMyComment(
                            $migration->migration_ticket,
                            date("d.m.Y H:i") . ": " . "Статус миграции $message->migration изменился на $message->status. Лог миграции: " .
                                $this->createUrl('/hardMigration/log', ['id' => $migration->obj_id])
                        );
                        break;
                }
            }
        }

        HardMigration::updateAll(['migration_status' => $message->status], ['obj_id' => $migration->obj_id]);

        $this->sendHardMigrationUpdated($migration->obj_id);
        $message->accepted();
    }

    public static function sendHardMigrationUpdated($id)
    {
        Yii::info("Sending to comet new data of hard migration #$id");

        $model = HardMigration::findByPk($id);

        $html = \Yii::$app->view->renderFile('@app/modules/Wtflow/views/hard-migration/_hardMigrationGrid.php', [
            'dataProvider' => $model->search(['obj_id' => $id]),
            'model' => $model,
        ]);

        Yii::info("html code generated");

        Yii::$app->webSockets->send('hardMigrationChanged', ['rr_id' => str_replace("/", "", "{$model->migration_name}_$model->migration_environment"), 'html' => $html]);
    }

    public function createUrl($route, $params)
    {
        \Yii::$app->urlManager->setBaseUrl('');
        array_unshift($params, $route);

        return Url::to($params, true);
    }
}
