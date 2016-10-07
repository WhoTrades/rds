<?php
/** @var $this yii\web\View */

$this->title = \Yii::$app->name . ' - Авторизация';
?>
<h2 style="text-align: center"><a href="<?=\Yii::$app->getModule('SingleLogin')->auth->getAuthUrl()?>">Авторизация через crm</h2>
