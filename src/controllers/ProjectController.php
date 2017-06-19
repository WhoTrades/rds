<?php
namespace app\controllers;

use app\models\Log;
use app\models\Worker;
use app\models\Project;
use yii\web\HttpException;
use app\models\RdsDbConfig;
use app\models\ProjectConfig;
use app\models\Project2worker;
use app\models\ProjectConfigHistory;

class ProjectController extends Controller
{
    public $pageTitle = 'Проекты';

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
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
        $searchModel    = new ProjectConfigHistory();
        $dataProvider   = $searchModel->search(\Yii::$app->request->get(), $id);

        return $this->render('view', [
            'model' => $this->loadModel($id),
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $model = new Project();

        if (isset($_POST['Project'])) {
            $model->attributes = $_POST['Project'];
            if ($model->save()) {
                foreach ($_POST['workers'] as $workerId) {
                    $p2w = new Project2worker();
                    $p2w->worker_obj_id = $workerId;
                    $p2w->project_obj_id = $model->obj_id;
                    $p2w->save();
                }

                $this->redirect(array('view', 'id' => $model->obj_id));
            }
        }


        $list = array();
        foreach ($model->project2workers as $val) {
            $list[$val->worker_obj_id] = $val;
        }

        return $this->render('create', array(
            'model' => $model,
            'list' => $list,
            'workers' => Worker::find()->all(),
        ));
    }

    /**
     * @param int $id
     * @return string
     */
    public function actionUpdateScriptMigration(int $id) : string
    {
        $model = $this->loadModel($id);

        if (isset($_POST['Project'])) {
            $model->attributes = $_POST['Project'];
            if ($model->save()) {
                $this->redirect(array('view', 'id' => $model->obj_id));
            }
        }

        return $this->render('update-script-migration', array(
            'project' => $model,
        ));
    }

    /**
     * @param int $id
     * @return string
     */
    public function actionUpdateConfigLocal(int $id) : string
    {
        $model = $this->loadModel($id);

        if (isset($_POST['Project'])) {
            $model->attributes = $_POST['Project'];
            if ($model->save()) {
                $this->redirect(array('view', 'id' => $model->obj_id));
            }
        }

        return $this->render('update-config-local', array(
            'project' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);
        $deployment_enabled = RdsDbConfig::get()->deployment_enabled;

        if (isset($_POST['Project']) && $deployment_enabled) {
            $model->attributes = $_POST['Project'];
            $transaction = $model->getDbConnection()->beginTransaction();
            $existingProject = Project::findByPk($model->obj_id);

            if ($model->save()) {
                Log::createLogMessage("Удалены все связки {$model->project_name}");
                Project2worker::deleteAll(array('project_obj_id' => $model->obj_id));
                foreach ($_POST['workers'] as $workerId) {
                    $projectWorker = new Project2worker();
                    $projectWorker->worker_obj_id = $workerId;
                    $projectWorker->project_obj_id = $model->obj_id;
                    $projectWorker->save();

                    Log::createLogMessage("Создана {$projectWorker->getTitle()}");
                }

                $needUpdateConfigs = false;

                foreach ($_POST['project_config'] as $filename => $content) {
                    /** @var $projectConfig ProjectConfig */
                    $projectConfig = ProjectConfig::findByAttributes([
                        'pc_filename'       => $filename,
                        'pc_project_obj_id' => $model->obj_id,
                    ]);

                    if (!$projectConfig) {
                        $projectConfig = new ProjectConfig();
                        $projectConfig->pc_project_obj_id = $model->obj_id;
                    }

                    if ($projectConfig->pc_content === $content) {
                        continue;
                    }

                    $diffStat = \Yii::$app->diffStat->getDiffStat(
                        str_replace("\r", "", $projectConfig->pc_content),
                        str_replace("\r", "", $content)
                    );

                    $diffStat = preg_replace('~\++~', '<span style="color: #32cd32">$0</span>', $diffStat);
                    $diffStat = preg_replace('~\-+~', '<span style="color: red">$0</span>', $diffStat);

                    $projectConfig->pc_content = $content;
                    if (!$projectConfig->validate(['pc_content'])) {
                        $model->addError($filename, $projectConfig->getFirstError('pc_content'));
                        continue;
                    }


                    $needUpdateConfigs = true;

                    $projectHistoryItem = new ProjectConfigHistory();
                    $projectHistoryItem->pch_project_obj_id = $model->obj_id;
                    $projectHistoryItem->pch_filename = $filename;
                    $projectHistoryItem->pch_config = $content;
                    $projectHistoryItem->pch_user_id = \Yii::$app->user->id;
                    $projectHistoryItem->save();


                    $projectConfig->save();

                    Log::createLogMessage("Изменение в конфигурации $existingProject->project_name/$filename:<br />
$diffStat<br />
<a href='" . \yii\helpers\Url::to(["/diff/project_config", 'id' => $projectHistoryItem->obj_id]) . "'>Посмотреть подробнее</a>
");
                }

                if ($needUpdateConfigs) {
                    $model->sendNewProjectConfigTasks();
                }

                if (!$model->hasErrors()) {
                    $transaction->commit();
                    $this->redirect(array('view', 'id' => $model->obj_id));
                } else {
                    $transaction->rollBack();
                }
            } else {
                $transaction->rollBack();
            }
        }

        $list = array();
        foreach ($model->project2workers as $val) {
            $list[$val->worker_obj_id] = $val;
        }

        return $this->render('update', array(
            'model' => $model,
            'list' => $list,
            'workers' => Worker::find()->all(),
            'deployment_enabled' => $deployment_enabled,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        Project2worker::deleteAll(['project_obj_id' => $id]);

        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax'])) {
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
        }
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $model = new Project(['scenario' => 'search']);
        if (isset($_GET['Project'])) {
            $model->attributes = $_GET['Project'];
        }

        return $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin()
    {
        $model = new Project(['scenario' => 'search']);
        if (isset($_GET['Project'])) {
            $model->attributes = $_GET['Project'];
        }

        return $this->render('admin', array(
            'model' => $model,
            'workers' => Worker::find()->all(),
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Project
     * @throws HttpException
     */
    public function loadModel($id)
    {
        $model = Project::findByPk($id);
        if ($model === null) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }
}
