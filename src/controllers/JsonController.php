<?php
namespace app\controllers;

use app\models\Project;
use app\modules\Whotrades\models\ToolJob;
use yii\base\Module;
use yii\web\HttpException;
use app\models\RdsDbConfig;
use app\modules\Whotrades\models\ToolJobStopped;
use app\models\ReleaseVersion;
use app\models\ReleaseRequest;
use app\modules\Wtflow\models\Developer;
use app\modules\Wtflow\models\WtFlowStat;
use app\modules\Wtflow\components\JiraApi;

class JsonController extends Controller
{
    /**
     * JsonController constructor.
     * @param string $id
     * @param Module $module
     * @param array $config
     */
    public function __construct($id, Module $module, array $config = [])
    {
        $this->enableCsrfValidation = false;
        parent::__construct($id, $module, $config);
    }

    public function actionGetReleaseRequests($project)
    {
        /** @var $Project Project */
        $Project = Project::findByAttributes(['project_name' => $project]);
        if (!$Project) {
            throw new HttpException(404, 'Project not found');
        }

        $releaseRequests = $Project->releaseRequests;

        $result = [];
        foreach ($releaseRequests as $releaseRequest) {
            /** @var $project Project */
            $result[] = array(
                'project' => $project,
                'version' => $releaseRequest->rr_build_version,
                'old_version' => $releaseRequest->rr_old_version,
            );
        }

        echo json_encode($result);
    }

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

    public function actionGetAllowedReleaseBranches()
    {
        $versions = ReleaseVersion::find()->all();

        $result = [];
        foreach ($versions as $version) {
            $result[] = 'release-'.$version->rv_version;
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
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
     * @return string
     */
    public function actionAddWtFlowStat()
    {
        $developer = Developer::findByAttributes(['whotrades_email' => $_POST['developer']]);
        
        if (!$developer) {
            return "Unknown developer " . $_POST['developer'];
        }

        $wtflow = new WtFlowStat();
        $wtflow->attributes = $_POST;
        $wtflow->developer_id = $developer->obj_id;
        $wtflow->log = implode("\n", $_POST['log'] ?? []);
        $wtflow->save();

        return "OK";
    }

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

            list(,$project, $version) = $ans;

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
                'obj_status_did' => \ServiceBase_IHasStatus::STATUS_ACTIVE,
            ]);
            if ($toolJob) {
                \Yii::$app->graphite->getGraphite()->gauge(
                    \GraphiteSystem\Metrics::dynamicName(
                        \GraphiteSystem\Metrics::SYSTEM__TOOL__TIMEREAL,
                        [$project, $toolJob->getLoggerTag() . "-" . $key]
                    ),
                    $val['time'] / $val['count']
                );
                \Yii::$app->graphite->getGraphite()->gauge(
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

    /**
     * Этот метод дергается скриптом updater.php на tst контуре, что бы понять нужно ли обновлять контур
     * Сделано в связи с регламентом http://wiki/pages/viewpage.action?pageId=72813392
     */
    public function actionGetTstUpdatingEnabled()
    {
        echo json_encode([
            'ok' => true,
            'enabled' => RdsDbConfig::get()->is_tst_updating_enabled
        ]);
    }

    public function actionStartTeamcityBuild($issueKey, $branch = 'master')
    {
        $branch = trim($branch) ? html_entity_decode(strip_tags($branch)) : 'master';
        $issueKey = trim($issueKey) ? html_entity_decode(strip_tags($issueKey)) : '';

        $jiraApi = new JiraApi(\Yii::$app->debugLogger);
        try {
            $ticket = $jiraApi->getTicketInfo($issueKey);
        } catch(\CompanyInfrastructure\Exception\Jira\TicketNotFound $e) {
            echo json_encode(["ERROR" => 'Wrong issue key given: "'.$issueKey.'"']);
            return;
        }

        if (!isset($ticket['fields']['components'])) {
            echo json_encode(["ERROR" => 'Issue "'.$issueKey.'" have not allowed components']);
            return;
        }

        $result = array();
        $projectsAllowed = \Yii::$app->params['teamCityProjectAllowed'];
        $parameterName = \Yii::$app->params['teamCityBuildComponentParameter'];
        $teamCity = new \CompanyInfrastructure\WtTeamCityClient(\Yii::$app->debugLogger);
        $components = $ticket['fields']['components'];

        foreach ($projectsAllowed as $project) {
            $buildList = $teamCity->getBuildTypesList($project);
                foreach ($buildList as $build) {
                    $buildId = (string)$build['id'];
                    try {
                        $parameter = $teamCity->getBuildTypeParameterByName($buildId, $parameterName);
                    } catch (\Exception $e) {
                        /** go forward **/
                        continue;
                    }

                    $teamcityJiraComponent = (string)$parameter;
                    foreach ($components as $component) {
                        if (strtolower($teamcityJiraComponent) == strtolower($component['name'])) {
                            $result[] = $teamCity->startBuild(
                                $buildId, $branch, "Teamcity build run on request"
                            );
                        }
                    }
                }

        }

        echo json_encode(["OK" => true, 'TEAMCITY_RESPONSE' => json_encode($result)]);
        return;
    }

    public function actionGetCronJobsStatus()
    {
        $sql = "select
          project_name,
          command,
          last_run_time,
          extract(epoch from (NOW() - last_run_time)) as sec_from_last_run
        from cronjobs.cpu_usage
        join cronjobs.tool_job ON cpu_usage.key=tool_job.key AND tool_job.obj_status_did=1
        ";

        $result = \Yii::$app->db->createCommand($sql)->queryAll();

        header("Content-type: application/javascript");
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Точка входа для Telegram Callback и (пока) регистрация callback-хука который будет вызывать сервис Tekegram на каждое входящее сообщение
     *
     * @throws \TelegramBot\Api\Exception
     *
     * @todo Установку хука надо перенести отсюда в более подходящее место, возможно в пункты меню интеграции RDS
     * @todo Добавить обработку того, что это действительно обращение к нашему боту (проверять токен в урле)
     *
     * @return void
     */
    public function actionTelegramCallback()
    {
        /* @var \app\modules\Wtflow\components\TelegramBot $telegramBot */
        $telegramBot = \Yii::$app->getModule('Wtflow')->telegramBot;

        if (\Yii::$app->request->isPost) {
            $telegramBot->processTelegramCallback();
        } else {
            $telegramBot->setWebhook(\Yii::$app->request->getAbsoluteUrl());
        }

        echo 'OK';
    }

    /**
     * Метод API, который дергает JIRA посредством web хука при переводе задачи из статуса "Ready for staging" назад
     * @param string $ticket
     *
     * @throws ApplicationException
     * @return void
     */
    public function actionTicketRemovedFromStaging($ticket)
    {
        if (false === strpos($ticket, 'WTS') && false === strpos($ticket, 'WTT')) {
            echo 'Skip ticket';

            return;
        }

        $action = new \Action\Git\RebuildBranch();
        $action->run('staging', 'JIRA-hook', (new \RdsSystem\Factory(\Yii::$app->debugLogger))->getMessagingRdsMsModel(), false);

        echo 'OK';
    }
}