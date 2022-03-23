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

use samdark\log\PsrMessage;
use whotrades\RdsSystem\lib\Exception\CommandExecutorException;
use Yii;
use whotrades\RdsSystem\Cron\SingleInstanceController;
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
                Yii::info("Process {$typeName} migrations of project {$project->project_name}");

                Yii::info('Start to add or update migrations');
                try {
                    Yii::$app->migrationService->addOrUpdateExistedMigrations($typeName, $releaseRequest);
                } catch (CommandExecutorException $e) {
                    Yii::error(new PsrMessage("Can't add or update migrations. Error: " . $e->getMessage(), ['exception' => $e]));
                }

                Yii::info('Start to delete not existed migrations');
                try {
                    Yii::$app->migrationService->deleteNonExistentMigrations($typeName, $releaseRequest);
                } catch (CommandExecutorException $e) {
                    Yii::error(new PsrMessage("Can't delete non existent migrations. Error: " . $e->getMessage(), ['exception' => $e]));
                }
            }
        }

        if (!Yii::$app->params['migrationAutoApplicationEnabled']) {
            Yii::info('Skip auto application. Disabled in config.');

            return;
        }

        Yii::info('Start auto application of migrations');
        Yii::$app->migrationService->applyCanBeAutoAppliedMigrations();
    }
}
