<?php
/* @var $this BuildController */
/* @var $model Build */

$this->breadcrumbs=array(
	'Builds'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'Manage Build', 'url'=>array('admin')),
);
?>

<h1>Create Build</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>