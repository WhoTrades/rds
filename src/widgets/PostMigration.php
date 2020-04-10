<?php
namespace whotrades\rds\widgets;

use yii\base\Widget;
use whotrades\rds\models\Migration;

class PostMigration extends Widget
{
    /**
     * @return string
     */
    public function run()
    {
        if (!\Yii::$app->user->can('developer')) {
            return "";
        }

        $postMigrationAllowTimestamp = date('c', strtotime("-" . \Yii::$app->params['postMigrationStabilizeDelay']));

        $readyPostMigrationsCount = Migration::find()->
            andWhere(['migration_type' => Migration::TYPE_ID_POST])->
            andWhere(['IN', 'obj_status_did', [Migration::STATUS_PENDING, Migration::STATUS_FAILED_APPLICATION]])->
            andWhere(['<', 'obj_created', $postMigrationAllowTimestamp])->
            count();

        return $this->render('@app/views/widgets/PostMigration', [
            'readyPostMigrationsCount' => $readyPostMigrationsCount,
        ]);
    }
}
