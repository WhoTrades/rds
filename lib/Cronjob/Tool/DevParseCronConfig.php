<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=DevParseCronConfig -vv
 */

use app\models\Project;
use app\modules\Whotrades\models\ToolJob;
use app\models\ReleaseRequest;

class Cronjob_Tool_DevParseCronConfig extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    /**
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [
            'project' => [
                'desc' => 'Project of cron config',
                'valueRequired' => true,
                'required' => true,
            ],
            'filename' => [
                'desc' => 'Filename of cron config',
                'valueRequired' => true,
                'required' => true,
            ],
        ] + parent::getCommandLineSpec();
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     * @return int
     * @throws Exception
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $filename = $cronJob->getOption('filename');
        $project = $cronJob->getOption('project');

        if (!file_exists($filename) || !is_readable($filename)) {
            $this->debugLogger->error("File $filename not exists or not readable");

            return 1;
        }

        $Project = Project::findByAttributes(['project_name' => $project]);
        if (!$Project) {
            $this->debugLogger->error("Project $project not exists at DB");

            return 2;
        }

        /** @var $rr ReleaseRequest */
        $rr = ReleaseRequest::findByAttributes([
            'rr_project_obj_id' => $Project->obj_id,
            'rr_build_version' => $Project->project_current_version,
        ]);

        if (!$rr) {
            $this->debugLogger->error("No any release request for project=$project");

            return 3;
        }

        $rr->rr_cron_config = file_get_contents($filename);

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            ToolJob::deleteAll([
                'project_obj_id' => $Project->obj_id,
            ]);

            $rr->parseCronConfig($rr->getBuildTag());

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return 0;
    }
}
