<?php
namespace app\controllers;

use app\models\Log;
use app\models\Project2worker;
use HardMigrationBase;
use yii\web\HttpException;
use app\models\HardMigration;
use RdsSystem;
use Exception;

class HardMigrationController extends Controller
{
    public $pageTitle = 'Тяжелые миграции';

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $model = new HardMigration(['scenario' => 'search']);
        if (isset($_GET['HardMigration'])) {
            $model->attributes = $_GET['HardMigration'];
        }


        return $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function actionStart($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Запущена {$migration->getTitle()}");

        if (!$migration->canBeStarted() && !$migration->canBeRestarted()) {
            throw new Exception("Invalid migration status");
        }

        $migration->migration_status = HardMigration::MIGRATION_STATUS_STARTED;
        $migration->save(false);

        foreach ($migration->project->project2workers as $p2w) {
            /** @var Project2worker $p2w*/
            $worker = $p2w->worker;
            (new RdsSystem\Factory(\Yii::$app->debugLogger))->getMessagingRdsMsModel($migration->migration_environment)->sendHardMigrationTask(
                $worker->worker_name,
                new \RdsSystem\Message\HardMigrationTask(
                    $migration->migration_name,
                    $migration->project->project_name,
                    $migration->project->project_current_version
                )
            );
        }

        $this->redirect('/hardMigration/index');
    }

    /**
     * @param int $id
     */
    public function actionRestart($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Перезапущена {$migration->getTitle()}");
        $this->actionStart($id);
    }

    /**
     * @param int $id
     */
    public function actionPause($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("ПОставлена на паузу {$migration->getTitle()}");
        $this->sendUnixSignalAndRedirect($id, HardMigrationBase::SIGNAL_PAUSE, HardMigration::MIGRATION_STATUS_PAUSED);
    }

    /**
     * @param int $id
     */
    public function actionResume($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Снята с паузы {$migration->getTitle()}");
        $this->sendUnixSignalAndRedirect($id, HardMigrationBase::SIGNAL_RESUME, HardMigration::MIGRATION_STATUS_IN_PROGRESS);
    }

    /**
     * @param int $id
     */
    public function actionStop($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Остановлена {$migration->getTitle()}");
        $this->sendUnixSignalAndRedirect($id, HardMigrationBase::SIGNAL_STOP);
    }

    private function sendUnixSignalAndRedirect($id, $signal, $newStatus = null)
    {
        $migration = $this->loadModel($id);

        foreach ($migration->project->project2workers as $p2w) {
            /** @var Project2worker $p2w */
            $worker = $p2w->worker;
            (new RdsSystem\Factory(\Yii::$app->debugLogger))->getMessagingRdsMsModel($migration->migration_environment)->sendUnixSignal(
                $worker->worker_name,
                new \RdsSystem\Message\UnixSignal($migration->migration_pid, $signal)
            );
        }

        if ($newStatus) {
            HardMigration::updateByPk($id, ['migration_status' => $newStatus]);
        }

        $this->redirect('/hardMigration/index');
    }

    /**
     * @param int $id
     * @return string
     */
    public function actionLog($id)
    {
        $migration = $this->loadModel($id);

        return $this->render('log', ['migration' => $migration]);
    }

    /**
     * @param int $id
     * @return HardMigration
     * @throws HttpException
     */
    public function loadModel($id)
    {
        $model = HardMigration::findByPk($id);
        if ($model === null) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }
}
