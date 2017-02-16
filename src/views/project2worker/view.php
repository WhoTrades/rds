<?php
/* @var $this Project2workerController */
/* @var $model Project2worker */

$this->menu=array(
	array('label'=>'List Project2worker', 'url'=>array('index')),
	array('label'=>'Create Project2worker', 'url'=>array('create')),
	array('label'=>'Update Project2worker', 'url'=>array('update', 'id'=>$model->obj_id)),
	array('label'=>'Delete Project2worker', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->obj_id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Project2worker', 'url'=>array('admin')),
);
?>

<h1>View Project2worker #<?php echo $model->obj_id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'obj_id',
		'obj_created',
		'obj_modified',
		'obj_status_did',
		'worker_obj_id',
		'project_obj_id',
	),
)); ?>
