<?php
class JsonController extends Controller
{
    public function actionGetReleaseRequests($project)
    {
        /** @var $Project Project */
        $Project = Project::model()->findByAttributes(['project_name' => $project]);
        if (!$Project) {
            throw new CHttpException(404, 'Project not found');
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

    public function actionGetAllowedReleaseBranches()
    {
        $versions = ReleaseVersion::model()->findAll();

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
        $project = Project::model()->findByAttributes(['project_name' => $projectName]);

        echo $project ? $project->project_current_version : null;
    }

    public function actionGetSecondsAfterLastSuccessfulRunOfMaintenanceTool($toolName)
    {
        $tool = MaintenanceTool::model()->findByAttributes([
            'mt_name' => $toolName,
        ]);

        if (!$tool) {
            throw new CHttpException(404, "Tool $toolName not found");
        }

        $c = new CDbCriteria();
        $c->limit = 1;
        $c->order = 'obj_created desc';
        $c->compare('mtr_maintenance_tool_obj_id', $tool->obj_id);
        $c->compare('mtr_status', MaintenanceToolRun::STATUS_DONE);
        $mtr = MaintenanceToolRun::model()->find($c);

        //an: если тул ни разу не запускали - считаем что его запустили в начале времен
        if (!$mtr) {
            return time();
        }

        echo time() - strtotime($mtr->obj_created);
    }

    public function actionAddWtFlowStat()
    {
        $developer = Developer::model()->findByAttributes(['whotrades_email' => $_POST['developer']]);
        
        if (!$developer) {
            echo "Unknown developer ".$_POST['developer'];
            return;
        }

        $wtflow = new WtFlowStat();
        $wtflow->attributes = $_POST;
        $wtflow->developer_id = $developer->obj_id;
        $wtflow->log = implode("\n", $_POST['log']);
        $wtflow->save();
    }

    public function actionGetDisabledCronjobs()
    {
        $c = new CDbCriteria();
        $c->addCondition("stopped_till > NOW()");
        $list = ToolJobStopped::model()->findAll($c);
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

        $transaction = Yii::app()->db->beginTransaction();

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
                ':duration' => (int)$val['time'] / $val['count'],
            ];

            $row = Yii::app()->db->createCommand("SELECT * FROM cronjobs.add_cronjobs_cpu_usage(:project, :key, :cpu, :last_run, :exit_code, :duration)")->queryRow(true, $bind);

            $toolJob = ToolJob::model()->findByAttributes([
                'key' => $key,
                'obj_status_did' => \ServiceBase_IHasStatus::STATUS_ACTIVE,
            ]);
            if ($toolJob) {
                Yii::app()->graphite->getGraphite()->gauge(
                    \GraphiteSystem\Metrics::dynamicName(
                        \GraphiteSystem\Metrics::SYSTEM__TOOL__TIMEREAL,
                        [$project, $toolJob->getLoggerTag() . "-" . $key]
                    ),
                    $val['time'] / $val['count']
                );
                Yii::app()->graphite->getGraphite()->gauge(
                    \GraphiteSystem\Metrics::dynamicName(
                        \GraphiteSystem\Metrics::SYSTEM__TOOL__TIMECPU,
                        [$project, $toolJob->getLoggerTag() . "-" . $key]
                    ),
                    ($val['CPUUser'] + $val['CPUSystem']) / $val['count']
                );
            }

            Yii::app()->webSockets->send('updateToolJonPerformanceStats', [
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

        $transaction->commit();

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
            'enabled' => \RdsDbConfig::get()->is_tst_updating_enabled
        ]);
    }

    public function actionStartTeamcityBuild($issueKey, $branch = 'master')
    {
        $branch = trim($branch) ? html_entity_decode(strip_tags($branch)) : 'master';
        $issueKey = trim($issueKey) ? html_entity_decode(strip_tags($issueKey)) : '';

        $jiraApi = new JiraApi(Yii::app()->debugLogger);
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
        $projectsAllowed = Yii::app()->params['teamCityProjectAllowed'];
        $parameterName = Yii::app()->params['teamCityBuildComponentParameter'];
        $teamCity = new CompanyInfrastructure\WtTeamCityClient();
        $components = $ticket['fields']['components'];

        foreach ($projectsAllowed as $project) {
            foreach ($teamCity->getBuildTypesList($project) as $buildList) {
                foreach ($buildList as $build) {
                    $buildId = (string)$build['id'];
                    try {
                        $parameter = $teamCity->getBuildTypeParameterByName($buildId, $parameterName);
                    } catch (\Exception $e) {
                        /** go forward **/
                        continue;
                    }

                    $teamcityJiraComponent = (string)$parameter['value'];
                    foreach ($components as $component) {
                        if (strtolower($teamcityJiraComponent) == strtolower($component['name'])) {
                            $result[] = $teamCity->startBuild(
                                $buildId, $branch, "Teamcity build run on request"
                            );
                        }
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

        $result = Yii::app()->db->createCommand($sql)->queryAll();

        header("Content-type: application/javascript");
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}