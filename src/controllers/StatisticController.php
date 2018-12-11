<?php
namespace whotrades\rds\controllers;

use whotrades\rds\models\Project;
use yii\web\HttpException;
use whotrades\rds\models\ReleaseRequest;

class StatisticController extends ControllerApiBase
{
    /**
     * @param string $projectName
     * @return string
     * @throws HttpException
     */
    public function actionGetLastBuildTime($projectName)
    {
        $project = Project::findByAttributes(array('project_name' => $projectName));

        if (!$project) {
            throw new HttpException(404, 'unknown project');
        }

        $releaseRequest = ReleaseRequest::find()->
            andWhere(['rr_project_obj_id' => $project->obj_id])->
            andWhere(['not', ['rr_built_time' => null]])->
            orderBy('obj_id desc')->
            one();

        if ($releaseRequest && $releaseRequest->rr_built_time) {
            return strtotime($releaseRequest->rr_built_time) - strtotime($releaseRequest->rr_build_started ?: $releaseRequest->obj_created);
        } else {
            return "unknown";
        }
    }
}
