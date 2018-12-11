<?php

namespace whotrades\rds\controllers;

use whotrades\rds\models\Worker;
use yii\web\HttpException;
use yii\data\ActiveDataProvider;

class WorkerController extends ControllerRestrictedBase
{
    public $pageTitle = 'Сборщики';

    /**
     * Displays a particular model.
     * @param int $id the ID of the model to be displayed
     * @return string
     */
    public function actionView($id)
    {
        return $this->render('view', array(
            'model' => $this->loadModel($id),
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string
     */
    public function actionCreate()
    {
        $model = new Worker();

        if (isset($_POST['Worker'])) {
            $model->attributes = $_POST['Worker'];
            if ($model->save()) {
                $this->redirect(array('view', 'id' => $model->obj_id));
            }
        }

        return $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     * @return string
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        if (isset($_POST['Worker'])) {
            $model->attributes = $_POST['Worker'];
            if ($model->save()) {
                $this->redirect(array('view', 'id' => $model->obj_id));
            }
        }

        return $this->render('update', array(
            'model' => $model,
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
     * @return string
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider(['query' => Worker::find()]);

        return $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Manages all models.
     * @return string
     */
    public function actionAdmin()
    {
        $model = new Worker(['scenario' => 'search']);
        if (isset($_GET['Worker'])) {
            $model->attributes = $_GET['Worker'];
        }

        return $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Worker the loaded model
     * @throws HttpException
     */
    public function loadModel($id)
    {
        $model = Worker::findByPk($id);
        if ($model === null) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }
}
