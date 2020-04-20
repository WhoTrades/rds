<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\controllers;

use whotrades\rds\models\Migration;
use whotrades\rds\models\Log;
use yii\web\HttpException;

class MigrationController extends ControllerRestrictedBase
{
    public $pageTitle = 'Migrations';

    /**
     * @return string
     */
    public function actionIndex()
    {
        $model = new Migration(['scenario' => 'search']);
        if (isset($_GET['Migration'])) {
            $model->attributes = $_GET['Migration'];
        }

        return $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * @param int $migrationId
     */
    public function actionApply($migrationId)
    {
        /** @var Migration $migration */
        $migration = $this->loadModel($migrationId);
        Log::createLogMessage("Выполнена миграция '{$migration->migration_name}' проекта '{$migration->project->project_name}'");
        $migration->apply();

        $this->redirect('/migration/index');
    }

    /**
     * @param int $migrationId
     */
    public function actionRollBack($migrationId)
    {
        /** @var Migration $migration */
        $migration = $this->loadModel($migrationId);
        Log::createLogMessage("Откачена миграция '{$migration->migration_name}' проекта '{$migration->project->project_name}'");
        $migration->rollBack();

        $this->redirect('/migration/index');
    }

    /**
     * @param int $migrationId
     *
     * @return string
     */
    public function actionViewLog($migrationId)
    {
        /** @var Migration $migration */
        $migration = $this->loadModel($migrationId);

        if (!$migration) {
            return 'There is not migration with id ' . $migrationId;
        }

        return $migration->migration_log;
    }

    /**
     * @param int $migrationId
     *
     * @throws HttpException
     */
    public function actionAutoApplyDisable($migrationId)
    {
        /** @var Migration $migration */
        $migration = $this->loadModel($migrationId);
        Log::createLogMessage("Деактивировано авто выполнение миграции '{$migration->migration_name}' проекта '{$migration->project->project_name}'");
        $migration->autoApplyDisable();

        $this->redirect('/migration/index');
    }

    /**
     * @param int $migrationId
     *
     * @throws HttpException
     */
    public function actionAutoApplyEnable($migrationId)
    {
        /** @var Migration $migration */
        $migration = $this->loadModel($migrationId);
        Log::createLogMessage("Активировано авто выполнение миграции 'э'{$migration->migration_name}' проекта '{$migration->project->project_name}'");
        $migration->autoApplyEnable();

        $this->redirect('/migration/index');
    }

    /**
     * @param int $id
     *
     * @return Migration
     *
     * @throws HttpException
     */
    public function loadModel($id)
    {
        $model = Migration::findByPk($id);
        if ($model === null) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }
}
