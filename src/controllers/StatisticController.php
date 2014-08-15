<?php
class StatisticController extends Controller
{
    public function actionGetLastBuildTime($projectName)
    {
        $project = Project::model()->findByAttributes(array('project_name' => $projectName));

        if (!$project) {
            throw new CHttpException(404, 'unknown project');
        }

        $c = new CDbCriteria();
        $c->order = 'obj_id desc';
        $c->condition = 'rr_project_obj_id='.(int)$project->obj_id." AND NOT rr_built_time IS NULL";
        /** @var $releaseRequest ReleaseRequest*/
        $releaseRequest = ReleaseRequest::model()->find($c);

        if ($releaseRequest && $releaseRequest->rr_built_time) {
            echo strtotime($releaseRequest->rr_built_time) - strtotime($releaseRequest->obj_created);
        } else {
            echo "unknown";
        }
    }

    public function actionGetProjectBuildsToDeleteLastCallTime()
    {
        include('JsonController.php');
        echo time() - CoreLight::getInstance()->getServiceBaseCacheKvdpp()->get(JsonController::LAST_PACKAGE_REMOVE_CALL_TIME_KEY);
    }
}
