<?php
namespace app\modules\SingleLogin\controllers;

use app\controllers\Controller;

class DefaultController extends Controller
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
