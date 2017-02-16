<?php
/* @var $this ReleaseVersionController */
/* @var $model ReleaseVersion */

$this->menu=array(
	array('label'=>'List ReleaseVersion', 'url'=>array('index')),
	array('label'=>'Manage ReleaseVersion', 'url'=>array('admin')),
);
?>

<h1>Create ReleaseVersion</h1>

<?php echo $this->render('_form', array('model'=>$model)); ?>