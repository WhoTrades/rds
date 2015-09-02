<?php
class MonitoringController extends Controller
{
    public function actionCronjobsLastRun()
    {
        $sql = "select project_name, command, last_run_time, NOW() - last_run_time as lag from cronjobs.cpu_usage
        join cronjobs.tool_job ON cpu_usage.key=tool_job.key AND tool_job.obj_status_did=1
        WHERE (
            (command like '% * * * * *%' and last_run_time < NOW() - interval '20 minutes') OR
            (command like '% * * * *%' and last_run_time < NOW() - interval '2 hours') OR
          (command like '% * * *%' and last_run_time < NOW() - interval '2 days')
        )";

        $data = Yii::app()->db->createCommand($sql)->queryAll();

        header("Content-type: text/plain");

        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}
