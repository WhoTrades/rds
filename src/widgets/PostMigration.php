<?php
class PostMigration extends CWidget
{
    public function init()
    {
        if (Yii::app()->user->isGuest) {
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

        $ids = Yii::app()->db->createCommand($sql)->queryColumn([':status' => \ServiceBase_IHasStatus::STATUS_ACTIVE]);
        $c = new CDbCriteria();
        $c->compare('releaserequest.obj_id', $ids);
        $c->compare('releaserequest.rr_post_migration_status', '<>' . ReleaseRequest::MIGRATION_STATUS_UP);
        $c->addCondition("rr_new_post_migrations != '' and rr_new_post_migrations != '[]' and not rr_new_post_migrations is null");
        $c->with = ['project'];
        $releaseRequests = ReleaseRequest::model()->findAll($c);

        $this->render('application.views.widgets.PostMigration', [
            'releaseRequests' => $releaseRequests,
        ]);
    }

    public function run()
    {

    }
}
