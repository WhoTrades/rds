<?php
/* @var $this SiteController */
/* @var $model LoginForm */
/* @var $form CActiveForm  */

$this->pageTitle=Yii::app()->name . ' - Login';
$this->breadcrumbs=array(
	'Login',
);
?>
<h2 style="text-align: center"><a href="<?=Yii::app()->getModule('SingleLogin')->auth->getAuthUrl()?>">Авторизация через crm</h2>