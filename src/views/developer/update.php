<?php
/* @var $this DeveloperController */
/* @var $model Developer */

$this->breadcrumbs=array(
	'Developers'=>array('index'),
	$model->obj_id=>array('view','id'=>$model->obj_id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Developer', 'url'=>array('index')),
	array('label'=>'Create Developer', 'url'=>array('create')),
	array('label'=>'View Developer', 'url'=>array('view', 'id'=>$model->obj_id)),
);
?>

<h1>Update Developer <?php echo $model->obj_id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>