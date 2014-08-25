<?php
class PostMigration extends CWidget
{
    public function init()
    {
        $projects = Project::model()->findAll();

        $releaseRequests = [];

        foreach ($projects as $project) {
            /** @var $project Project */
            $version = $project->project_current_version;
            list($release) = explode(".", $version);

            $c = new CDbCriteria();
            $c->compare('rr_project_obj_id', $project->obj_id);
            $c->compare('rr_build_version', "<=".($version-2));
            $c->compare('rr_status', [ReleaseRequest::STATUS_INSTALLED, ReleaseRequest::STATUS_OLD]);

            $c->order = 'rr_build_version desc';
            $c->limit = 1;

            if ($rr = ReleaseRequest::model()->find($c)) {
                if ($rr->rr_new_post_migrations) {
                    $releaseRequests[] = $rr;
                }
            }
        }

        $this->render('application.views.widgets.PostMigration', [
            'releaseRequests' => $releaseRequests,
        ]);
    }

    public function run()
    {

    }
}
