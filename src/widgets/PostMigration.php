<?php
namespace whotrades\rds\widgets;

use whotrades\rds\components\Status;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\PostMigration as PostMigrationModel;
use Yii;

class PostMigration extends \yii\base\Widget
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

        $readyPostMigrationsCount = PostMigrationModel::find()->
            andWhere(['IN', 'pm_status', [PostMigrationModel::STATUS_PENDING, PostMigrationModel::STATUS_FAILED]])->
            andWhere(['<', 'obj_created', $postMigrationAllowTimestamp])->
            count();

        return $this->render('@app/views/widgets/PostMigration', [
            'readyPostMigrationsCount' => $readyPostMigrationsCount,
        ]);
    }
}
