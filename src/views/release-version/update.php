<?php
/* @var $this ReleaseVersionController */
/* @var $model ReleaseVersion */

$this->breadcrumbs=array(
	'Release Versions'=>array('index'),
	$model->obj_id=>array('view','id'=>$model->obj_id),
	'Update',
);

$this->menu=array(
	array('label'=>'List ReleaseVersion', 'url'=>array('index')),
	array('label'=>'Create ReleaseVersion', 'url'=>array('create')),
	array('label'=>'View ReleaseVersion', 'url'=>array('view', 'id'=>$model->obj_id)),
	array('label'=>'Manage ReleaseVersion', 'url'=>array('admin')),
);
?>

<h1>Update ReleaseVersion <?php echo $model->obj_id; ?></h1>

<?php echo $this->render('_form', array('model'=>$model)); ?>