<?php
namespace app\widgets;

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
                        order by rr_build_version desc
                        limit 1
                    ) as rr_obj_id from rds.project
                )  as subquery where not rr_obj_id is null";

        $ids = \Yii::$app->db->createCommand($sql)->queryColumn();
        $c = new CDbCriteria();
        $c->compare('t.obj_id', $ids);
        $c->compare('t.rr_post_migration_status', '<>'.ReleaseRequest::MIGRATION_STATUS_UP);
        $c->addCondition("rr_new_post_migrations != '' and rr_new_post_migrations != '[]' and not rr_new_post_migrations is null");
        $c->with = ['project'];
        $releaseRequests = ReleaseRequest::findAll($c);

        $this->render('application.views.widgets.PostMigration', [
            'releaseRequests' => $releaseRequests,
        ]);
    }

    public function run()
    {

    }
}
