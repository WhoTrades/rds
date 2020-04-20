<?php
/**
 * Tool updates migrations:
 *  - add new migrations if they exist
 *  - fill created date and jira ticket from git
 *  - update status of existed migrations if they've been executed manually
 *  - send commands fo executing migrations which are ready to be executed
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 *
 * @example php yii.php migration/update
 */
namespace whotrades\rds\commands;

use Yii;
use whotrades\RdsSystem\Cron\SingleInstanceController;
use whotrades\RdsSystem\lib\CommandExecutor;
use whotrades\rds\models\Migration;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;

class MigrationController extends SingleInstanceController
{
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

            foreach ([Migration::TYPE_PRE, Migration::TYPE_POST, Migration::TYPE_HARD] as $typeName) {
                foreach ([MigrateController::MIGRATION_COMMAND_NEW_ALL, MigrateController::MIGRATION_COMMAND_HISTORY] as $migrationCommand) {
                    $text = $this->executeScript(
                        $project->script_migration_new,
                        "/tmp/migration-{$typeName}-script-",
                        [
                            'project' => $project->project_name,
                            'version' => $releaseRequest->rr_build_version,
                            'type' => $typeName,
                            'command' => $migrationCommand,
                        ]
                    );

                    Yii::info("Output: $text");
                    $lines = explode("\n", str_replace("\r", "", $text));
                    $migrationNameList = array_filter($lines);
                    $migrationNameList = array_map('trim', $migrationNameList);

                    Yii::$app->migrationService->createOrUpdateListByCommand($migrationNameList, $typeName, $migrationCommand, $project, $releaseRequest);
                }
            }
        }

        Yii::$app->migrationService->applyCanBeAutoAppliedMigrations();
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
}
