<?php

class CronJobsController extends Controller
{
    const KILL_SIGNAL = 15; //SIGTERM

    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    public function accessRules()
    {
        return array(
            array('allow',
                'users'=>array('@'),
            ),
            array('deny',  // deny all users
                'users'=>array('*'),
            ),
        );
    }



    public function actionIndex($project = 'comon')
    {
        $projects = Project::model()->findAll();

        $packages = array_map(function(Project $project){
            return $project->project_name."-".$project->project_current_version;
        }, $projects);

        $c = new CDbCriteria();
        $c->compare('package', $packages);
        $c->with = 'project';
        $c->order = '"group"';

        $list = ToolJob::model()->findAll($c);

        $cronJobs = [];
        $keys = [];

        foreach ($list as $val) {
            /** @var $val ToolJob */
            if (!isset($cronJobs[$val->project_obj_id])) {
                $cronJobs[$val->project_obj_id] = [
                    'project' => $val->project,
                    'cronJobs' => [],
                ];
            }
            $cronJobs[$val->project_obj_id]['cronJobs'][] = $val;
            $keys[] = $val->key;
        }

        $cpuUsagesOrdered = [];
        $cpuUsages = CpuUsage::model()->findAllByAttributes(['key' => array_unique($keys)]);
        foreach ($cpuUsages as $cpuUsage) {
            /** @var $cpuUsage CpuUsage*/
            $cpuUsagesOrdered[$cpuUsage->key][$cpuUsage->project_name] = $cpuUsage;
        }



        $this->render('index', [
            'cronJobs' => $cronJobs,
            'project' => $project,
            'cpuUsages' => $cpuUsagesOrdered,
            'cpuUsageLastTruncate' => RdsDbConfig::get()->cpu_usage_last_truncate,
        ]);
    }

    public function actionStop($key, $projectId, $interval, $url = '/cronjobs/index')
    {
        $project = Project::model()->findByPk($projectId);
        if (!$project) {
            return;
        }

        if (!$stopper = ToolJobStopped::model()->findByAttributes(['key' => $key, 'project_obj_id' => $project->obj_id])) {
            $stopper = new ToolJobStopped();
            $stopper->key = $key;
            $stopper->project_name = $project->project_name;
            $stopper->project_obj_id = $project->obj_id;
        }

        $stopper->stopped_till = date("Y-m-d H:i:s", strtotime("+$interval"));
        $stopper->save();

        Log::createLogMessage("Остановлена фоновая задача $key на $interval");

        $this->redirect($url);
    }

    public function actionStart($key, $projectId, $url)
    {
        if ($stopper = ToolJobStopped::model()->findByAttributes(['key' => $key, 'project_obj_id' => $projectId])) {
            $stopper->delete();
            Log::createLogMessage("Запущена фоновая задача $key");
        }


        $this->redirect($url);
    }

    public function actionKill($key, $project, $signal = self::KILL_SIGNAL)
    {
        $model = (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel();

        $servers = [];
        $result = [];
        $res = null;

        do {
            if (!empty($res)) {
                $servers[] = $res->server;
                $result[] = $res;
            }

            $res = $model->sendToolKillTask(
                new RdsSystem\Message\Tool\KillTask($key, $project, $signal), RdsSystem\Message\Tool\KillResult::type()
            );
        } while (!in_array($res->server, $servers));

        $data = array_map(function(RdsSystem\Message\Tool\KillResult $val){
            return ['server' => $val->server, 'processes' => $val->result];
        }, $result);

        usort($data, function($a, $b){
            return $a['server'] > $b['server'];
        });

        Log::createLogMessage("Убиты процессы с сигналом -$signal: ".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->renderPartial('kill', ['result' => $data]);
    }

    public function actionGetInfo($key, $project)
    {
        $model = (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel();

        $servers = [];
        $result = [];
        $res = null;

        do {
            if (!empty($res)) {
                $servers[] = $res->server;
                $result[] = $res;
            }

            $res = $model->sendToolGetInfoTask(
                new RdsSystem\Message\Tool\GetInfoTask($key, $project), RdsSystem\Message\Tool\GetInfoResult::type()
            );
        } while (!in_array($res->server, $servers));

        $data = array_map(function(RdsSystem\Message\Tool\GetInfoResult $val){
            return ['server' => $val->server, 'processes' => $val->result];
        }, $result);

        usort($data, function($a, $b){
            return $a['server'] > $b['server'];
        });

        $this->renderPartial('getInfo', ['result' => $data]);
    }

    public function actionTruncateCpuUsage()
    {
        $config = RdsDbConfig::get();
        CpuUsage::model()->deleteAll();
        $config->cpu_usage_last_truncate = date('Y-m-d H:i:s');
        $config->save();

        Log::model()->createLogMessage("Cronjobs cpu usage обнулены");
    }

    public function actionCpuUsageReport()
    {
        $sql = "select project_name, \"group\", command, substring(command from 'local2.info -t (.*)'), cpu_time / 1000 as round, cpu_usage.key, project_name
                from cronjobs.cpu_usage
                join rds.project USING(project_name)
                JOIN cronjobs.tool_job ON cronjobs.cpu_usage.key=cronjobs.tool_job.key and project.obj_id=project_obj_id AND tool_job.obj_status_did=".\ServiceBase_IHasStatus::STATUS_ACTIVE."
                order by cpu_time desc";
        $data = Yii::app()->db->createCommand($sql)->queryAll();

        $this->render('cpuUsageReport', [
            'data' => $data,
        ]);
    }
}
