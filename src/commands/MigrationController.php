<?php
/**
 * @example php yii.php migration/update
 */
namespace whotrades\rds\commands;

use whotrades\rds\models\ReleaseRequest;
use Yii;
use whotrades\rds\models\Project;
use whotrades\rds\models\Migration;
use whotrades\RdsSystem\Cron\SingleInstanceController;
use whotrades\RdsSystem\lib\CommandExecutor;
use whotrades\RdsSystem\Message;
use whotrades\RdsSystem\Factory as RdsSystemFactory;
use whotrades\RdsSystem\Model\Rabbit\MessagingRdsMs;

class MigrationController extends SingleInstanceController
{
    /**
     * @var MessagingRdsMs
     */
    private $messagingModel;

    /**
     * @return void
     */
    public function actionUpdate()
    {
        $projectList = Project::find()->where(['not', ['script_migration_new' => null]])->all();

        /** @var Project $project */
        foreach ($projectList as $project) {
            /** @var ReleaseRequest $releaseRequest */
            $releaseRequest = ReleaseRequest::getUsedReleaseByProjectId($project->obj_id);

            foreach ([Migration::TYPE_PRE, Migration::TYPE_POST, Migration::TYPE_HARD] as $type) {
                foreach ([MigrateController::MIGRATION_COMMAND_NEW_ALL, MigrateController::MIGRATION_COMMAND_HISTORY] as $migrationCommand) {
                    $text = $this->executeScript(
                        $project->script_migration_new,
                        "/tmp/migration-{$type}-script-",
                        [
                            'project' => $project->project_name,
                            'version' => $releaseRequest->rr_build_version,
                            'type' => $type,
                            'command' => $migrationCommand,
                        ]
                    );

                    Yii::info("Output: $text");
                    $lines = explode("\n", str_replace("\r", "", $text));
                    $migrations = array_filter($lines);
                    $migrations = array_map('trim', $migrations);
                    $this->getMessagingModel()->sendMigrations(
                        new Message\ReleaseRequestMigrations($project->project_name, $releaseRequest->rr_build_version, $migrations, $type, $migrationCommand)
                    );
                }
            }
        }
    }

    /**
     * @param string $script
     * @param string $scriptPrefix
     * @param array $env
     *
     * @return string
     *
     * @throws \whotrades\RdsSystem\lib\CommandExecutorException
     */
    private function executeScript($script, $scriptPrefix, array $env)
    {
        $commandExecutor = new CommandExecutor();

        $scriptFilename = $scriptPrefix . uniqid() . ".sh";
        file_put_contents($scriptFilename, str_replace("\r", "", $script));
        chmod($scriptFilename, 0777);

        $result = $commandExecutor->executeCommand("$scriptFilename 2>&1", $env);

        unlink($scriptFilename);

        return $result;
    }

    /**
     * @return MessagingRdsMs
     */
    private function getMessagingModel()
    {
        if (!$this->messagingModel) {
            $this->messagingModel = (new RdsSystemFactory())->getMessagingRdsMsModel();
        }

        return $this->messagingModel;
    }
}
