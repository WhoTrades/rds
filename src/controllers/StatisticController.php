<?php
namespace app\controllers;

use app\models\Project;
use yii\web\HttpException;
use app\models\ReleaseRequest;

class StatisticController extends Controller
{
    const LAST_PACKAGE_REMOVE_CALL_TIME_KEY = 'RDS::actionGetProjectBuildsToDelete::last_call_time';

    public function actionGetLastBuildTime($projectName)
    {
        $project = Project::findByAttributes(array('project_name' => $projectName));

        if (!$project) {
            throw new HttpException(404, 'unknown project');
        }

        $releaseRequest = ReleaseRequest::find()->andWhere(['rr_project_obj_id' => $project->obj_id])->andWhere(['not', ['rr_built_time' => null]])->orderBy('obj_id desc')->one();

        if ($releaseRequest && $releaseRequest->rr_built_time) {
            echo strtotime($releaseRequest->rr_built_time) - strtotime($releaseRequest->rr_build_started ?: $releaseRequest->obj_created);
        } else {
            echo "unknown";
        }
    }

    public function actionGetProjectBuildsToDeleteLastCallTime()
    {
        echo time() - \CoreLight::getInstance()->getServiceBaseCacheKvdpp()->get(self::LAST_PACKAGE_REMOVE_CALL_TIME_KEY);
    }
}
