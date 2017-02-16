<?php
/* @var $model Project2worker */

$this->params['menu']=array(
	array('label'=>'List Project2worker', 'url'=>array('index')),
	array('label'=>'Manage Project2worker', 'url'=>array('admin')),
);
?>

<h1>Create Project2worker</h1>

<?php echo $this->render('_form', array('model'=>$model)); ?>