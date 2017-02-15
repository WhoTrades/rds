<?php
namespace app\controllers;

use app\models\Project;
use app\models\ReleaseRequest;

class StatisticController extends Controller
{
    const LAST_PACKAGE_REMOVE_CALL_TIME_KEY = 'RDS::actionGetProjectBuildsToDelete::last_call_time';

    public function actionGetLastBuildTime($projectName)
    {
        $project = Project::findByAttributes(array('project_name' => $projectName));

        if (!$project) {
            throw new CHttpException(404, 'unknown project');
        }

        $c = new CDbCriteria();
        $c->order = 'obj_id desc';
        $c->condition = 'rr_project_obj_id='.(int)$project->obj_id." AND NOT rr_built_time IS NULL";
        /** @var $releaseRequest ReleaseRequest*/
        $releaseRequest = ReleaseRequest::find($c);

        if ($releaseRequest && $releaseRequest->rr_built_time) {
            echo strtotime($releaseRequest->rr_built_time) - strtotime($releaseRequest->rr_build_started ?: $releaseRequest->obj_created);
        } else {
            echo "unknown";
        }
    }

    public function actionGetProjectBuildsToDeleteLastCallTime()
    {
        echo time() - CoreLight::getInstance()->getServiceBaseCacheKvdpp()->get(self::LAST_PACKAGE_REMOVE_CALL_TIME_KEY);
    }
}
