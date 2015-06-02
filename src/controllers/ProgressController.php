<?php
use \GraphiteSystem\Graphite;

class ProgressController extends Controller
{
    public function actionSetTime($action, $time, $taskId)
    {
        /** @var $build Build */
        $build = Build::model()->findByPk($taskId);
        if (!$build) {
            throw new CHttpException(404, 'Build not found');
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
        $this->sendGraphiteActionData(
            $build->releaseRequest->project->project_name,
            $build->releaseRequest->rr_build_version,
            $lastAction,
            $timeDiff
        );

        echo json_encode(['ok' => true]);
    }

    /**
     * @param $project
     * @param $build
     * @param $action
     * @param $value
     */
    private function sendGraphiteActionData($project, $build, $action, $value)
    {
        $graphite = new Graphite(Yii::app()->params['graphite']);
        $graphite->gauge(
            \GraphiteSystem\Metrics::dynamicName(
                \GraphiteSystem\Metrics::PROJECT__ACTION__TIME,
                [
                    $project, $action
                ]
            ),

            $value
        );
    }

    private function sendProgressbarChanged(Build $build)
    {
        $info = $build->getProgressbarInfo();
        if ($info) {
            list($percent, $key) = $info;
            $comet = Yii::app()->realplexor;
            $comet->send('progressbarChanged', ['build_id' => $build->obj_id, 'percent' => $percent, 'key' => $key]);
        }
    }
}