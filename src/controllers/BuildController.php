<?php
namespace app\controllers;

use app\models\Build;
use yii\web\HttpException;

class BuildController extends Controller
{
    public $layout = '//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
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
                'actions' => array('index', 'view'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('create', 'update'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete'),
                'users' => array('admin'),
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
        $this->render('view', array(
            'model' => $this->loadModel($id),
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

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Build'])) {
            $model->attributes = $_POST['Build'];
            if ($model->save()) {
                $this->redirect(array('view', 'id' => $model->obj_id));
            }
        }

        $this->render('update', array(
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
	 */
	public function actionIndex()
	{
        return $this->actionAdmin();
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model = new Build(['scenario' => 'search']);
		if(isset($_GET['Build']))
			$model->attributes=$_GET['Build'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Build the loaded model
	 * @throws HttpException
	 */
	public function loadModel($id)
	{
		$model=Build::findByPk($id);
		if($model===null)
			throw new HttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Build $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='build-form')
		{
			echo CActiveForm::validate($model);
			\Yii::$app->end();
		}
	}

    public function cliColorsToHtml($text)
    {
        $foregroundColors['black'] = '0;30';
        $foregroundColors['darkgray'] = '1;30';
        $foregroundColors['blue'] = '0;34';
        $foregroundColors['lightblue'] = '1;34';
        $foregroundColors['green'] = '0;32';
        $foregroundColors['green  '] = '32';
        $foregroundColors['lightgreen'] = '1;32';
        $foregroundColors['#FF5555'] = '00;36';
        $foregroundColors['lightcyan'] = '01;36';
        $foregroundColors['red'] = '0;31';
        $foregroundColors['#FF5555'] = '01;31';
        $foregroundColors['red    '] = '31;1';
        $foregroundColors['purple'] = '0;35';
        $foregroundColors['lightpurple'] = '1;35';
        $foregroundColors['brown'] = '0;33';
        $foregroundColors['#00BBA1'] = '00;36';
        $foregroundColors['yellow'] = '1;33';
        $foregroundColors['lightgray'] = '0;37';
        $foregroundColors['white'] = '1;37';

        $backgroundColors['black'] = '40';
        $backgroundColors['red'] = '41';
        $backgroundColors['green'] = '42';
        $backgroundColors['yellow'] = '43';
        $backgroundColors['blue'] = '44';
        $backgroundColors['magenta'] = '45';
        $backgroundColors['lightgray'] = '47';
        $temp = $foregroundColors;
        foreach ($temp as $key => $val) {
            $foregroundColors[$key." "] = "0$val";
        }
        $text = preg_replace_callback("~\e\[([\d;]+)m~",
            function ($mathes) use ($foregroundColors, $backgroundColors)  {
                $defaultBackgroundColor = "transparent";
                $defaultTextColor = "inherit";
                if (false !== $color = array_search($mathes[1], $foregroundColors)) {
                    return "</font><font style='color: $color'>";
                }

                if (false === strpos($mathes[1], ";")) {
                    return "</font><font style='color: $defaultTextColor; background-color: $defaultBackgroundColor'>";
                }

                list($background, $foreground) = explode(";", $mathes[1]);

                $textColor = $defaultTextColor;
                foreach ($foregroundColors as $code => $color) {
                    $list = explode(";",$color);
                    if ($list[0] == $foreground) {
                        $textColor = $code;
                        break;
                    }
                }

                $backgroundColor = array_search($background, $backgroundColors) ;
                $backgroundColor = $backgroundColor === false ? $defaultBackgroundColor : $backgroundColor;

                if ($backgroundColor == $defaultBackgroundColor && $textColor == $defaultTextColor) {
                    return "</font><font style='color: $defaultTextColor; background-color: $defaultBackgroundColor'>";
                }
                return "</font><font style='color: $textColor; background-color: $backgroundColor'>";
            }, $text);

        $text = str_replace("\e[m", "</font>", $text);

        return "<div style='background: black; color: #AAAAAA'>".nl2br($text)."</div>";
    }
}
