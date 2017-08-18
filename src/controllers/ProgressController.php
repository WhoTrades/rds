<?php
namespace app\controllers;

use app\models\Build;
use yii\web\HttpException;
use \GraphiteSystem\Graphite;

class ProgressController extends Controller
{
    public function actionSetTime($action, $time, $taskId)
    {
        /** @var $build Build */
        $build = Build::findByPk($taskId);
        if (!$build) {
            throw new HttpException(404, 'Build not found');
        }

        $data = json_decode($build->build_time_log, true);

        $lastTime = end($data) ?: 0;
        $lastAction = key($data) ?: "init";

        $timeDiff = abs((float)$time - $lastTime);
        $data[$action] = (float)$time;

        asort($data);
        $build->build_time_log = json_encode($data);
        $build->save();

        $this->sendProgressbarChanged($build);

        echo json_encode(['ok' => true]);
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