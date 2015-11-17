<?php
/* @var $this ProjectController */
/* @var $model Project */

$this->breadcrumbs=array(
	'Projects'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'Manage Project', 'url'=>array('admin')),
);
?>

<h1>Create Project</h1>

<?php $this->renderPartial('_form', array('model'=>$model, 'list' => $list, 'workers' => $workers)); ?>