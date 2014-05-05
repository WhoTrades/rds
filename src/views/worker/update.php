<?php
/* @var $this WorkerController */
/* @var $model Worker */

$this->breadcrumbs=array(
	'Workers'=>array('index'),
	$model->obj_id=>array('view','id'=>$model->obj_id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Worker', 'url'=>array('index')),
	array('label'=>'Create Worker', 'url'=>array('create')),
	array('label'=>'View Worker', 'url'=>array('view', 'id'=>$model->obj_id)),
	array('label'=>'Manage Worker', 'url'=>array('admin')),
);
?>

<h1>Update Worker <?php echo $model->obj_id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>