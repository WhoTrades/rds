<?php
use RdsSystem\Message;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

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
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model  = $this->getMessagingModel($cronJob);

        $model->readHardMigrationStatus(false, function(Message\HardMigrationStatus $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received changing status of hard migration: ".json_encode($message));
            $this->actionUpdateHardMigrationStatus($message, $model);
        });

        $this->debugLogger->message("Start listening");
        $this->waitForMessages($model, $cronJob);
    }


    public function actionUpdateHardMigrationStatus(Message\HardMigrationStatus $message, MessagingRdsMs $model)
    {
        /** @var $migration HardMigration */
        $migration = HardMigration::model()->findByAttributes([
            'migration_name' => $message->migration,
            'migration_environment' => $model->getEnv(),
        ]);

        if (!$migration) {
            $this->debugLogger->error("Can't find migration $message->migration, environment={$model->getEnv()}");
            $message->accepted();
            return;
        }


        //an: В жиру пишем только факт накатывания миграций на прод
        if ($model->getEnv() == 'main' && $migration->migration_status != $message->status) {
            if (\Config::getInstance()->serviceRds['jira']['repostMigrationStatus']) {
                $jira = new JiraApi($this->debugLogger);
                switch ($message->status) {
                    case HardMigration::MIGRATION_STATUS_NEW:
                        //an: Это означает что миграцию пытались запустить, но миграция оказалась ещё не готова к запуску. Просто ничего не делаем
                        break;
                    case HardMigration::MIGRATION_STATUS_IN_PROGRESS:
                        $this->addCommentOrAppendMyComment($jira, $migration->migration_ticket, "Запущена миграция $message->migration. Лог миграции: ".$this->createUrl('/hardMigration/log', ['id' => $migration->obj_id]));
                        break;
                    case HardMigration::MIGRATION_STATUS_DONE:
                        $this->addCommentOrAppendMyComment($jira, $migration->migration_ticket, "Выполнена миграция $message->migration. Лог миграции: ".$this->createUrl('/hardMigration/log', ['id' => $migration->obj_id]));

                        $jiraMove = new JiraMoveTicket();
                        $jiraMove->attributes = [
                            'jira_ticket' => $migration->migration_ticket,
                            'jira_direction' => JiraMoveTicket::DIRECTION_UP,
                        ];

                        $this->debugLogger->message("Adding ticket {$migration->migration_ticket} for moving up");

                        if (!$jiraMove->save()) {
                            $this->debugLogger->error("Can't save JiraMoveTicket, errors: ".json_encode($jiraMove->errors));
                        }

                        break;
                    case HardMigration::MIGRATION_STATUS_FAILED:
                        $this->addCommentOrAppendMyComment($jira, $migration->migration_ticket, "Завершилась с ошибкой миграция $message->migration. Лог миграции: ".$this->createUrl('/hardMigration/log', ['id' => $migration->obj_id]));
                        break;
                    default:
                        $this->addCommentOrAppendMyComment($jira, $migration->migration_ticket, "Статус миграции $message->migration изменился на $message->status. Лог миграции: ".$this->createUrl('/hardMigration/log', ['id' => $migration->obj_id]));
                        break;
                }
            }
        }

        HardMigration::model()->updateByPk($migration->obj_id, ['migration_status' => $message->status]);

        $this->sendHardMigrationUpdated($migration->obj_id);
        $message->accepted();
    }

    /**
     * Метод смотрит последний комментарий в тикете и смотрит его владельца. И в зависимости от того является ли автором RDS либо изменяет комментарий, либо добавляет новый
     *
     * @param JiraApi $jira
     * @param string $ticket
     * @param string $text
     */
    private function addCommentOrAppendMyComment(JiraApi $jira, $ticket, $text)
    {
        $text = date("d.m.Y H:i").": ".$text;
        $lastComment = end($jira->getTicketInfo($ticket)['fields']['comment']['comments']);
        if ($lastComment['author']['name'] == $jira->getUserName()) {
            $this->debugLogger->debug("Updating last comment {$lastComment['self']}");
            $jira->updateComment($ticket, $lastComment['id'], $lastComment['body']."\n".$text);
        } else {
            $this->debugLogger->debug("Adding new comment with text=$text");
            $jira->addComment($ticket, $text);
        }
    }

    public static function sendHardMigrationUpdated($id)
    {
        /** @var $debugLogger \ServiceBase_IDebugLogger */
        $debugLogger = Yii::app()->debugLogger;

        $debugLogger->message("Sending to comet new data of hard migration #$id");
        Yii::app()->assetManager->setBasePath(Yii::getPathOfAlias('application')."/../main/www/assets/");
        Yii::app()->assetManager->setBaseUrl("/assets/");
        Yii::app()->urlManager->setBaseUrl('/');
        $filename = Yii::getPathOfAlias('application.views.hardMigration._hardMigrationRow').'.php';

        list($controller, $action) = Yii::app()->createController('/');
        $controller->setAction($controller->createAction($action));
        Yii::app()->setController($controller);
        $model = HardMigration::model();
        $model->obj_id = $id;
        $rowTemplate = include($filename);
        $widget = Yii::app()->getWidgetFactory()->createWidget(Yii::app(),'yiistrap.widgets.TbGridView', [
            'dataProvider'=>new CActiveDataProvider($model, $model->search()),
            'columns'=>$rowTemplate,
            'rowCssClassExpression' => function(){return 'rowItem';},
        ]);
        $widget->init();
        ob_start();
        $widget->run();
        $html = ob_get_clean();
        $debugLogger->message("html code generated");

        /** @var $migration HardMigration */
        $migration = HardMigration::model()->findByPk($id);
        $comet = Yii::app()->realplexor;
        $comet->send('hardMigrationChanged', ['rr_id' => str_replace("/", "", "{$migration->migration_name}_$migration->migration_environment"), 'html' => $html]);
        $debugLogger->message("Sended");
    }

    public function createUrl($route, $params)
    {
        Yii::app()->urlManager->setBaseUrl('');
        return Yii::app()->createAbsoluteUrl($route, $params);
    }
}
