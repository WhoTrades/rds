<?php
namespace app\widgets;

use namespace app\models\ReleaseRequest;

class PostMigration extends \yii\base\Widget
{
    public function init()
    {
        if (\Yii::$app->user->isGuest) {
            return;
        }
        $sql = "select * from (
                    select (
                        select obj_id from rds.release_request where
                        rr_status IN ('old', 'installed')
                        and rr_project_obj_id=project.obj_id
                        and rr_build_version <= ((string_to_array(project_current_version,'.'))[1])::varchar
                        and obj_status_did = :status
                        order by rr_build_version desc
                        limit 1
                    ) as rr_obj_id from rds.project
                )  as subquery where not rr_obj_id is null";

        $ids = \Yii::$app->db->createCommand($sql)->bindValue(':status', \ServiceBase_IHasStatus::STATUS_ACTIVE)->queryColumn();

        $releaseRequests = ReleaseRequest::find()->with('project')->andWhere(['in', 'releaserequest.obj_id', $ids])
            ->andWhere(['<>', 'releaserequest.rr_post_migration_status', ReleaseRequest::MIGRATION_STATUS_UP])
            ->andWhere('rr_new_post_migrations != \'\' and rr_new_post_migrations != \'[]\' and not rr_new_post_migrations is null')
            ->all();

        $this->render('application.views.widgets.PostMigration', [
            'releaseRequests' => $releaseRequests,
        ]);
    }

    public function run()
    {

    }
}
