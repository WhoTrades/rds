<?php
/* @var $this WorkerController */
/* @var $model Worker */

$this->menu=array(
	array('label'=>'List Worker', 'url'=>array('index')),
	array('label'=>'Create Worker', 'url'=>array('create')),
	array('label'=>'View Worker', 'url'=>array('view', 'id'=>$model->obj_id)),
	array('label'=>'Manage Worker', 'url'=>array('admin')),
);
?>

<h1>Update Worker <?php echo $model->obj_id; ?></h1>

<?php echo $this->render('_form', array('model'=>$model)); ?>