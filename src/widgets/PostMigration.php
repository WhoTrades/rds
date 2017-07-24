<?php
namespace app\widgets;

use app\models\ReleaseRequest;
use Yii;

class PostMigration extends \yii\base\Widget
{
    /**
     * @return string
     */
    public function run()
    {
        if (\Yii::$app->user->isGuest) {
            return "";
        }

        $interval = Yii::$app->params['garbageCollector']['minTimeAtProd'];

        $sql = "select * from (
                    select (
                        select obj_id from rds.release_request where
                        rr_status IN ('old', 'installed')
                        and rr_project_obj_id=project.obj_id
                        and rr_last_time_on_prod <= NOW() - interval '$interval'
                        and obj_status_did = :status
                        order by obj_id desc
                        limit 1
                    ) as rr_obj_id from rds.project
                )  as subquery where not rr_obj_id is null";

        $ids = \Yii::$app->db->createCommand($sql)->bindValue(':status', \ServiceBase_IHasStatus::STATUS_ACTIVE)->queryColumn();

        $releaseRequests = ReleaseRequest::find()->with('project')->andWhere(['in', 'release_request.obj_id', $ids])
            ->andWhere(['<>', 'release_request.rr_post_migration_status', ReleaseRequest::MIGRATION_STATUS_UP])
            ->andWhere('rr_new_post_migrations != \'\' and rr_new_post_migrations != \'[]\' and not rr_new_post_migrations is null')
            ->all();

        return $this->render('@app/views/widgets/PostMigration', [
            'releaseRequests' => $releaseRequests,
        ]);
    }
}
