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
}

