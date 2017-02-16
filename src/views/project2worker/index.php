<?php
/* @var $this Project2workerController */
/* @var $dataProvider CActiveDataProvider */

$this->menu=array(
	array('label'=>'Create Project2worker', 'url'=>array('create')),
	array('label'=>'Manage Project2worker', 'url'=>array('admin')),
);
?>

<h1>Project2workers</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
