<?php
namespace whotrades\rds\controllers;

use whotrades\rds\components\Status;
use whotrades\rds\models\Project;
use whotrades\rds\modules\Whotrades\models\ToolJob;
use whotrades\rds\modules\Whotrades\models\ToolJobStopped;
use yii\web\HttpException;
use whotrades\rds\models\ReleaseRequest;

class JsonController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Возвращает список пакетов, установленных на PROD сервера
     * @param string $project название проекта (Поле в БД project.project_name)
     * @param int    $limit   максимальное количество результатов. Работает только если $project указан
     * @param string $format  формат ответа. null (json) или не null (plain text)
     * @throws HttpException
     */
    public function actionGetInstalledPackages($project = null, $limit = null, $format = null)
    {
        $limit = $limit ?: 5;
        $result = [];

        $releaseRequests = ReleaseRequest::find()->andWhere(['in', 'rr_status', ReleaseRequest::getInstalledStatuses()])->orderBy('obj_id desc');

        if ($project) {
            $projectObj = Project::findOne([
                'project_name' => $project,
            ]);

            if (!$projectObj) {
                throw new HttpException(404, "Project $project not found");
            }
            $releaseRequests->andWhere(['rr_project_obj_id' => $projectObj->obj_id])->limit($limit);
        }
        $releaseRequests = $releaseRequests->all();

        foreach ($releaseRequests as $releaseRequest) {
            /** @var $releaseRequest ReleaseRequest */
            $result[] = $releaseRequest->getBuildTag();
        }

        if ($format === null) {
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            echo implode(" ", $result);
        }
    }

    /**
     * Метод используется git хуком update.php, который проверяет можно ли команде CRM пушить в хотрелизную ветку после кодфриза и до релиза
     * @param string $projectName
     */
    public function actionGetProjectCurrentVersion($projectName)
    {
        /** @var $project Project */
        $project = Project::findByAttributes(['project_name' => $projectName]);

        echo $project ? $project->project_current_version : null;
    }

    /**
     * @deprecated
     * @remove me
     */
    public function actionGetDisabledCronjobs()
    {
        $list = ToolJobStopped::find()->where(['>', 'stopped_till', 'NOW()'])->all();
        $result = [];
        foreach ($list as $val) {
            $result[] = [$val->project_name, $val->key];
        }
        header("Content-type: application/javascript");
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @deprecated
     * @remove me
     */
    public function actionSetCronJobsStats()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode(["OK" => true]);

            return;
        }

        foreach ($data as $line => $val) {
            if (!preg_match('~--sys__package=([\w-]+)-([\d.]+)~', $line, $ans)) {
                continue;
            }

            list(, $project, $version) = $ans;

            if (!preg_match('~--sys__key=(\w+)~', $line, $ans)) {
                continue;
            }

            list(, $key) = $ans;

            $bind = [
                ':project' => $project,
                ':key' => $key,
                ':cpu' => 1000 * ($val['CPUUser'] + $val['CPUSystem']),
                ':last_run' => date("r", round($val['lastRunTimestamp'])),
                ':exit_code' => $val['errors'],
                ':duration' => (int) $val['time'] / $val['count'],
            ];

            $row = \Yii::$app->db->createCommand("SELECT * FROM cronjobs.add_cronjobs_cpu_usage(:project, :key, :cpu, :last_run, :exit_code, :duration)", $bind)
                    ->queryOne(\PDO::FETCH_ASSOC);

            $toolJob = ToolJob::findByAttributes([
                'key' => $key,
                'obj_status_did' => Status::ACTIVE,
            ]);
            if ($toolJob) {
                \Yii::$app->getModule('Whotrades')->graphite->getGraphite()->gauge(
                    \GraphiteSystem\Metrics::dynamicName(
                        \GraphiteSystem\Metrics::SYSTEM__TOOL__TIMEREAL,
                        [$project, $toolJob->getLoggerTag() . "-" . $key]
                    ),
                    $val['time'] / $val['count']
                );
                \Yii::$app->getModule('Whotrades')->graphite->getGraphite()->gauge(
                    \GraphiteSystem\Metrics::dynamicName(
                        \GraphiteSystem\Metrics::SYSTEM__TOOL__TIMECPU,
                        [$project, $toolJob->getLoggerTag() . "-" . $key]
                    ),
                    ($val['CPUUser'] + $val['CPUSystem']) / $val['count']
                );
            }

            \Yii::$app->webSockets->send('updateToolJobPerformanceStats', [
                'key' => $key,
                'version' => $version,
                'project' => $project,
                'package' => "$project-$version",
                'cpuTime' => $row['cpu_time'] / 1000,
                'last_exit_code' => $row['last_exit_code'],
                'last_run_time' => date("Y-m-d H:i:s", strtotime($row['last_run_time'])),
                'last_run_duration' => $row['last_run_duration'],
            ]);
        }

        echo json_encode(["OK" => true]);
    }
}
