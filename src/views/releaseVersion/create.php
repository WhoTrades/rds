<?php
/* @var $this ReleaseVersionController */
/* @var $model ReleaseVersion */

$this->breadcrumbs=array(
	'Release Versions'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List ReleaseVersion', 'url'=>array('index')),
	array('label'=>'Manage ReleaseVersion', 'url'=>array('admin')),
);
?>

<h1>Create ReleaseVersion</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>