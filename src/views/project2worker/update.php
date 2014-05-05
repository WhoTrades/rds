<?php
/* @var $this Project2workerController */
/* @var $model Project2worker */

$this->breadcrumbs=array(
	'Project2workers'=>array('index'),
	$model->obj_id=>array('view','id'=>$model->obj_id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Project2worker', 'url'=>array('index')),
	array('label'=>'Create Project2worker', 'url'=>array('create')),
	array('label'=>'View Project2worker', 'url'=>array('view', 'id'=>$model->obj_id)),
	array('label'=>'Manage Project2worker', 'url'=>array('admin')),
);
?>

<h1>Update Project2worker <?php echo $model->obj_id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>