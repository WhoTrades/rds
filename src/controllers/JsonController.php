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

        if ($task) {
            $result = array(
                'id' => $task->obj_id,
                'project' => $task->project->project_name,
            );
        } else {
            $result = array();
        }

        echo json_encode($result);
    }

    public function actionSendStatus()
    {
        /** @var $request CHttpRequest*/
        $request = Yii::app()->request;
        $taskId = $request->getPost('taskId');
        $status = $request->getPost('status');
        $version = $request->getPost('version');
        $attach = $request->getPost('attach');

        $build = Build::model()->findByPk($taskId);
        if (!$build) {
            throw new CHttpException(404, 'Build not found');
        }
        $project = $build->project;

        $build->build_status = $status;
        if ($attach) {
            $build->build_attach = $attach;
        }
        if ($version) {
            $build->build_version = $version;
        }


        switch ($status) {
            case Build::STATUS_INSTALLED:
                $title = "Success installed $project->project_name v.$version";
                $text = "Проект $project->project_name был собран и разложен по серверам. <a href='".$this->createAbsoluteUrl('build/view', array('id' => $build->obj_id))."'>Подробнее</a>";

                Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendReleaseRejectCustomNotification'}('success', $title, $version, $text);
                foreach (explode(",", \Yii::app()->params['notify']['status']['phones']) as $phone) {
                    Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                }
                break;
            case Build::STATUS_FAILED:
                $title = "Failed to install $project->project_name";
                $text = "Проект $project->project_name не удалось собрать. <a href='".$this->createAbsoluteUrl('build/view', array('id' => $build->obj_id))."'>Подробнее</a>";

                Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendReleaseRejectCustomNotification'}('success', $title, $version, $text);
                foreach (explode(",", \Yii::app()->params['notify']['status']['phones']) as $phone) {
                    Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                }
                break;
        }
        $build->save();

        echo json_encode(array("success" => true));
    }

    public function actionGetRejects($projectName)
    {
        $project = Project::model()->findByAttributes(array('project_name' => $projectName));
        $result = array();
        if ($project) {
            $rejects = $project->releaseRejects;
            foreach ($rejects as $reject) {
                $result[] = array(
                    'created' => $reject->obj_created,
                    'user' => $reject->rr_user,
                    'comment' => $reject->rr_comment,
                );
            }
        }

        echo json_encode($result);
    }
}

