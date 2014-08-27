<?php
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
        $data[$action] = (float)$time;

        asort($data);
        $build->build_time_log = json_encode($data);
        $build->save();

        echo json_encode(['ok' => true]);
    }
}