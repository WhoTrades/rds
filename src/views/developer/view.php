<?php
/* @var $this DeveloperController */
/* @var $model Developer */

$this->breadcrumbs=array(
	'Developers'=>array('index'),
	$model->obj_id,
);

$this->menu=array(
	array('label'=>'List Developer', 'url'=>array('index')),
	array('label'=>'Create Developer', 'url'=>array('create')),
	array('label'=>'Update Developer', 'url'=>array('update', 'id'=>$model->obj_id)),
	array('label'=>'Delete Developer', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->obj_id),'confirm'=>'Are you sure you want to delete this item?')),
);
?>

<h1>View Developer #<?php echo $model->obj_id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'obj_id',
		'whotrades_email',
		'finam_email',
	),
)); ?>
