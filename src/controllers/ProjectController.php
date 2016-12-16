<?php

class ProjectController extends Controller
{
    public $pageTitle = 'Проекты';

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array('allow',  // allow all users to perform 'index' and 'view' actions
                'users' => array('@'),
            ),
            array('deny',  // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
        $configHistoryModel = ProjectConfigHistory::model();
        $configHistoryModel->pch_project_obj_id = $id;
        $this->render('view', array(
            'model'              => $this->loadModel($id),
            'configHistoryModel' => $configHistoryModel,
        ));
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

        $this->render('create', array(
            'model' => $model,
            'list' => $list,
            'workers' => Worker::model()->findAll(),
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
            $model->project_config = str_replace("\r", "", $model->project_config);
            $transaction = $model->getDbConnection()->beginTransaction();
            $existingProject = Project::model()->findByPk($model->obj_id);

            if ($model->save()) {
                Log::createLogMessage("Удалены все связки {$model->project_name}");
                Project2worker::model()->deleteAllByAttributes(array('project_obj_id' => $model->obj_id));
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
                    $projectConfig = ProjectConfig::model()->findByAttributes([
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

                    $diffStat = Yii::app()->diffStat->getDiffStat(
                        str_replace("\r", "", $projectConfig->pc_content),
                        str_replace("\r", "", $content)
                    );

                    $diffStat = preg_replace('~\++~', '<span style="color: #32cd32">$0</span>', $diffStat);
                    $diffStat = preg_replace('~\-+~', '<span style="color: red">$0</span>', $diffStat);

                    $projectConfig->pc_content = $content;
                    if (!$projectConfig->validate(['pc_content'])) {
                        $model->addError($filename, $projectConfig->getError('pc_content'));
                        continue;
                    }


                    $needUpdateConfigs = true;

                    $projectHistoryItem = new ProjectConfigHistory();
                    $projectHistoryItem->pch_project_obj_id = $model->obj_id;
                    $projectHistoryItem->pch_filename = $filename;
                    $projectHistoryItem->pch_config = $content;
                    $projectHistoryItem->pch_user = \Yii::app()->user->name;
                    $projectHistoryItem->save();


                    $projectConfig->save();

                    Log::createLogMessage("Изменение в конфигурации $existingProject->project_name/$filename:<br />
$diffStat<br />
<a href='" . $this->createUrl("/diff/project_config", ['id' => $projectHistoryItem->obj_id]) . "'>Посмотреть подробнее</a>
");
                }

                if ($needUpdateConfigs) {
                    $configs = [];
                    foreach ($model->projectConfigs as $projectConfig) {
                        $configs[$projectConfig->pc_filename] = $projectConfig->pc_content;
                    }

                    (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel()->sendProjectConfig(
                        new \RdsSystem\Message\ProjectConfig(
                            $model->project_name,
                            $configs
                        )
                    );
                }

                if (!$model->hasErrors()) {
                    $transaction->commit();
                    $this->redirect(array('view', 'id' => $model->obj_id));
                } else {
                    $transaction->rollback();
                }
            } else {
                $transaction->rollback();
            }
        }

        $list = array();
        foreach ($model->project2workers as $val) {
            $list[$val->worker_obj_id] = $val;
        }

        $this->render('update', array(
            'model' => $model,
            'list' => $list,
            'deployment_enabled' => $deployment_enabled,
            'workers' => Worker::model()->findAll(),
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
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
        $dataProvider = new CActiveDataProvider('Project');

        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin()
    {
        $model = new Project('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Project'])) {
            $model->attributes = $_GET['Project'];
        }

        $this->render('admin', array(
            'model' => $model,
            'workers' => Worker::model()->findAll(),
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Project the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        $model = Project::model()->findByPk($id);
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Project $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'project-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
