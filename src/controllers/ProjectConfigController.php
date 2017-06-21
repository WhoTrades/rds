<?php

namespace app\controllers;

use app\models\Log;
use app\models\Project;
use Yii;
use app\models\ProjectConfig;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ProjectConfigController implements the CRUD actions for ProjectConfig model.
 */
class ProjectConfigController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @param int|null $projectId
     * @return string
     */
    public function actionIndex(int $projectId)
    {
        if (!$project = Project::findByPk($projectId)) {
            throw new NotFoundHttpException("Project #$project not found");
        }

        $query = ProjectConfig::find();
        $query->andWhere(['pc_project_obj_id' => $project->obj_id]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'project' => $project,
        ]);
    }

    /**
     * Displays a single ProjectConfig model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * @param int $projectId
     * @return string|\yii\web\Response
     */
    public function actionCreate(int $projectId)
    {
        $model = new ProjectConfig();
        $model->pc_project_obj_id = $projectId;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Log::createLogMessage("Создан конфигурационный файл {$model->project->project_name} $model->pc_filename");
            $model->project->sendNewProjectConfigTasts();

            return $this->redirect(['/project/update', 'id' => $model->pc_project_obj_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing ProjectConfig model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $oldAttributes = $model->attributes;

        $oldName = $model->pc_filename;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Log::createLogMessage("Переименован конфигурационный файл {$model->project->project_name} $oldName -> $model->pc_filename");
            if ($oldAttributes != $model->attributes) {
                $model->project->sendNewProjectConfigTasts();
            }

            return $this->redirect(['/project/update', 'id' => $model->pc_project_obj_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing ProjectConfig model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $project = $model->project;
        Log::createLogMessage("Создан конфигурационный файл {$model->project->project_name} $model->pc_filename");
        $model->delete();

        $project->sendNewProjectConfigTasts();

        return $this->redirect(['index']);
    }

    /**
     * Finds the ProjectConfig model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ProjectConfig
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ProjectConfig::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
