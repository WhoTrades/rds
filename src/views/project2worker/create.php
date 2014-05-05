<?php
/* @var $this Project2workerController */
/* @var $model Project2worker */

$this->breadcrumbs=array(
	'Project2workers'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Project2worker', 'url'=>array('index')),
	array('label'=>'Manage Project2worker', 'url'=>array('admin')),
);
?>

<h1>Create Project2worker</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>