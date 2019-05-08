<?php
namespace whotrades\rds\controllers;

use whotrades\rds\models\Build;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use yii\web\HttpException;

class ProgressController extends ControllerApiBase
{
    /**
     * @param string $action
     * @param float $time
     * @param string $project
     * @param string $version
     * @throws HttpException
     *
     * @return string
     */
    public function actionSetTime($action, $time, $project, $version)
    {
        /** @var $project Project */
        $project = Project::find()->where(['project_name' => $project])->one();
        if (!$project) {
            throw new HttpException(404, 'Project not found');
        }

        /** @var $rr ReleaseRequest */
        $rr = ReleaseRequest::find()->where(['rr_project_obj_id' => $project->obj_id, 'rr_build_version' => $version])->one();
        if (!$rr) {
            throw new HttpException(404, 'Release request not found');
        }

        $rr->addBuildTimeLog($action, $time);

        $this->sendProgressbarChanged($rr);

        return json_encode(['ok' => true]);
    }

    /**
     * @param ReleaseRequest $rr
     */
    private function sendProgressbarChanged(ReleaseRequest $rr)
    {
        if ($rr->builds && isset($rr->builds[0]) && ($info = $rr->builds[0]->getProgressbarInfo())) {
            list($percent, $key) = $info;
            \Yii::$app->webSockets->send('progressbarChanged', ['build_id' => $rr->builds[0]->obj_id, 'percent' => $percent, 'key' => $key]);
        }
    }
}
