<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\controllers;

use whotrades\rds\models\Migration;
use whotrades\rds\models\Log;
use yii\web\HttpException;
use whotrades\RdsSystem\Migration\LogAggregatorUrlInterface as MigrationLogAggregatorUrlInterface;

class MigrationController extends ControllerRestrictedBase
{
    public $pageTitle = 'Migrations';

    /** @var MigrationLogAggregatorUrlInterface */
    private $migrationLogAggregatorUrl;

    /**
     * {@inheritDoc}
     * @param MigrationLogAggregatorUrlInterface $migrationLogAggregatorUrl
     */
    public function __construct($id, $module, MigrationLogAggregatorUrlInterface $migrationLogAggregatorUrl, $config = null)
    {
        $this->migrationLogAggregatorUrl = $migrationLogAggregatorUrl;
        $config = $config ?? [];
        parent::__construct($id, $module, $config);
    }

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
            'migrationLogAggregatorUrl' => $this->migrationLogAggregatorUrl,
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

        return $this->render('viewLog', array(
            'migrationName' => $migration->migration_name,
            'log' => $migration->migration_log,
        ));
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
