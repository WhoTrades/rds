<?php
/* @var $this ProjectController */
/* @var $model Project */

$this->menu=array(
	array('label'=>'Manage Project', 'url'=>array('admin')),
);
?>

<h1>Create Project</h1>

<?php echo $this->render('_form', array('model'=>$model, 'list' => $list, 'workers' => $workers)); ?>