<?php
/**
 * Helper for sending web sockets updates
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\helpers;

use Yii;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Migration;

/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
class WebSockets
{
    /**
     * @param int $id
     */
    public static function sendReleaseRequestUpdated($id)
    {
        if (!$releaseRequest = ReleaseRequest::findByPk($id)) {
            return;
        }

        Yii::info("Sending to comet new data of releaseRequest $id");

        $html = Yii::$app->view->renderFile('@app/views/site/_releaseRequestGrid.php', [
            'dataProvider' => $releaseRequest->search(['obj_id' => $id]),
            'filterModel' => new ReleaseRequest(),
        ]);

        Yii::$app->webSockets->send('releaseRequestChanged', ['rr_id' => $id, 'html' => $html]);
        Yii::info("Sent");
    }


    /**
     * @param int $migrationId
     */
    public static function sendMigrationUpdated($migrationId)
    {
        Yii::info("Sending to comet new data of migration #$migrationId");

        $model = Migration::findByPk($migrationId);

        $html = Yii::$app->view->renderFile(__DIR__ . '/../views/migration/_migrationGrid.php', [
            'dataProvider' => $model->search(['obj_id' => $migrationId]),
            'model' => $model,
        ]);

        Yii::info("html code generated");

        Yii::$app->webSockets->send('migrationUpdated', ['rr_id' => str_replace("\\", "", $model->migration_name), 'html' => $html]);
    }
}