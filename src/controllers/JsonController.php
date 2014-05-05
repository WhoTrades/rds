<?php
class JsonController extends Controller
{
    public function actionGetTasks($worker)
    {
        $worker = Worker::model()->findByAttributes(array('worker_name' => $worker));
        if (!$worker) {
            throw new CHttpException('unknown worker');
        }

        $task = \Build::model()->findByAttributes(array(
            'build_worker_obj_id' => $worker->obj_id,
            'build_status' => Build::STATUS_NEW,
        ), array(
            'with' => 'project',
        ));

        $result = array(
            'id' => $task->obj_id,
            'project' => $task->project->project_name,
        );

        echo json_encode($result);
    }
}

