<?php

use RdsSystem\Message;
use app\models\RdsDbConfig;
use app\models\MaintenanceTool;
use yii\data\ActiveDataProvider;
use app\models\MaintenanceToolRun;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=AsyncReader_MaintenanceTool -vv
 */
class Cronjob_Tool_AsyncReader_MaintenanceTool extends RdsSystem\Cron\RabbitDaemon
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

        $model->readMaintenanceToolChangeStatus(false, function(Message\MaintenanceTool\ChangeStatus $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received changing status of maintenance tool: ".json_encode($message));
            $this->actionUpdateMaintenanceToolStatus($message, $model);
        });

        $model->readMaintenanceToolLogChunk(false, function(Message\MaintenanceTool\LogChunk $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received next log chunk of maintenance tool: ".json_encode($message));
            $this->actionSaveMaintenanceToolLogChunk($message, $model);
        });

        $model->readPreProdDown(false, function(Message\PreProd\Down $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Preprod is down: ".json_encode($message));
            $this->actionChangePreProdStatus(0, $model);
            $message->accepted();
        });

        $model->readPreProdUp(false, function(Message\PreProd\Up $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Preprod is up: ".json_encode($message));
            $this->actionChangePreProdStatus(1, $model);
            $message->accepted();
        });

        $this->debugLogger->message("Start listening");
        $this->waitForMessages($model, $cronJob);
    }

    public function actionUpdateMaintenanceToolStatus(Message\MaintenanceTool\ChangeStatus $message, RdsSystem\Model\Rabbit\MessagingRdsMs $model)
    {
        $message->accepted();

        $mtr = MaintenanceToolRun::findByPk($message->id);
        if (!$mtr) {
            $this->debugLogger->error("MTR id=$message->id not found");
            return;
        }

        MaintenanceToolRun::updateAll(['mtr_status' => $message->status, 'mtr_pid' => $message->pid], ['obj_id' => $message->id]);

        self::sendMaintenanceToolUpdated($message->id);

        $this->debugLogger->message("Status of MTR=$message->id updated to $message->status");
    }

    public function actionSaveMaintenanceToolLogChunk(Message\MaintenanceTool\LogChunk $message, RdsSystem\Model\Rabbit\MessagingRdsMs $model)
    {
        $message->accepted();

        $mtr = MaintenanceToolRun::findByPk($message->id);
        if (!$mtr) {
            $this->debugLogger->error("MTR id=$message->id not found");
            return;
        }

        $sql = "UPDATE ".MaintenanceToolRun::tableName()." SET mtr_log=COALESCE(mtr_log, '')||:log WHERE obj_id=:id";

        \Yii::$app->db->createCommand($sql, [
            'id' => $message->id,
            'log' => $message->text,
        ])->execute();

        $this->debugLogger->message("Log chunk of MTR=$message->id saved, length=".strlen($message->text).", log=".$message->text);


        $this->debugLogger->message("commet id=maintenance_tool_log_$message->id");

        Yii::$app->webSockets->send("maintenance_tool_log_$message->id", ['text' => $message->text]);

        /** @var $mtr MaintenanceToolRun */
        $mtr = MaintenanceToolRun::findByPk($message->id);
        if ($pair = $mtr->getProgressPercentAndKey()) {
            list($percent, $key) = $pair;
            Yii::$app->webSockets->send("maintenanceToolProgressbarChanged", ['id' => $message->id, 'percent' => $percent, 'key' => $key]);
            $this->debugLogger->message("Percentage of tool updated to percent=$percent");
        }

    }

    public function actionChangePreProdStatus($ok, MessagingRdsMs $model)
    {
        $config = RdsDbConfig::get();
        $config->preprod_online = $ok;
        $config->save();
    }

    public static function sendMaintenanceToolUpdated($id)
    {
        /** @var $debugLogger \ServiceBase_IDebugLogger */
        $debugLogger = \Yii::$app->debugLogger;

        /** @var $mtr MaintenanceToolRun */
        $mtr = MaintenanceToolRun::findByPk($id);
        if (!$mtr) {
            return;
        }

        $debugLogger->message("Sending to comet new data of maintenance tool run #$id");
        Yii::$app->assetManager->setBasePath(Yii::getPathOfAlias('application')."/../main/www/assets/");
        Yii::$app->assetManager->setBaseUrl("/assets/");
        Yii::$app->urlManager->setBaseUrl('/');
        $filename = \Yii::getPathOfAlias('application.views.maintenanceTool._maintenanceToolRow').'.php';

        list($controller, $action) = \Yii::$app->createController('/');
        $controller->setAction($controller->createAction($action));
        Yii::$app->setController($controller);
        $model = MaintenanceTool::model();
        $model->obj_id = $mtr->mtr_maintenance_tool_obj_id;

        $rowTemplate = include($filename);
        $widget = \Yii::$app->getWidgetFactory()->createWidget(Yii::$app,'yiistrap.widgets.TbGridView', [
            'dataProvider'=>new ActiveDataProvider($model, $model->search()),
            'columns'=>$rowTemplate,
            'rowCssClassExpression' => function(){return 'rowItem';},
        ]);
        $widget->init();
        ob_start();
        $widget->run();
        $html = ob_get_clean();
        $debugLogger->message("html code generated, html=".$html);

        /** @var $migration HardMigration */
        Yii::$app->webSockets->send('maintenanceToolChanged', ['id' => $mtr->mtr_maintenance_tool_obj_id, 'html' => $html]);
        $debugLogger->message("Sended");
    }

    public function createUrl($route, $params)
    {
        Yii::$app->urlManager->setBaseUrl('');
        array_unshift($params, $route);

        return \Yii::$app->urlManager->createAbsoluteUrl($params, true);
    }
}
