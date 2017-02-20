<?php
namespace app\modules\SingleLogin\controllers;

class DefaultController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}
