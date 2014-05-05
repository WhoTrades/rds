<?php
/* @var $this BuildController */
/* @var $model Build */

$this->breadcrumbs=array(
	'Builds'=>array('index'),
	$model->obj_id=>array('view','id'=>$model->obj_id),
	'Update',
);

$this->menu=array(
	array('label'=>'View Build', 'url'=>array('view', 'id'=>$model->obj_id)),
	array('label'=>'Manage Build', 'url'=>array('admin')),
);
?>

<h1>Update Build <?php echo $model->obj_id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>