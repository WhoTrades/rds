<?php
namespace app\controllers;

use app\models\Build;
use app\models\Project;
use app\models\ReleaseRequest;
use yii\web\HttpException;
use \GraphiteSystem\Graphite;

class ProgressController extends Controller
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
        $rr = Project::find()->where(['rr_project_obj_id' => $project->id, 'rr_build_version' => $version])->one();
        if (!$rr) {
            throw new HttpException(404, 'Release request not found');
        }

        /** @var $build Build */
        $build = Build::find()->where(['build_release_request_obj_id' => $rr->obj_id])->one();
        if (!$build) {
            throw new HttpException(404, 'Build not found');
        }

        $data = json_decode($build->build_time_log, true);
        $data[$action] = (float) $time;

        asort($data);
        $build->build_time_log = json_encode($data);
        $build->save();

        $this->sendProgressbarChanged($build);

        return json_encode(['ok' => true]);
    }

    private function sendProgressbarChanged(Build $build)
    {
        $info = $build->getProgressbarInfo();
        if ($info) {
            list($percent, $key) = $info;
            \Yii::$app->webSockets->send('progressbarChanged', ['build_id' => $build->obj_id, 'percent' => $percent, 'key' => $key]);
        }
    }
}
