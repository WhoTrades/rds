<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\controllers;

use whotrades\rds\models\Migration;

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
        $migration = Migration::findByPk($migrationId);
        $migration->apply();

        $this->redirect('/migration/index');
    }

    /**
     * @param int $migrationId
     */
    public function actionRollBack($migrationId)
    {
        /** @var Migration $migration */
        $migration = Migration::findByPk($migrationId);
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
        $migration = Migration::findByPk($migrationId);

        if (!$migration) {
            return 'There is not migration with id ' . $migrationId;
        }

        return $migration->migration_log;
    }
}
