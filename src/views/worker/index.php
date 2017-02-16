<?php
/* @var $dataProvider CActiveDataProvider */

$this->params['menu']=array(
	array('label'=>'Create Worker', 'url'=>array('create')),
	array('label'=>'Manage Worker', 'url'=>array('admin')),
);
?>

<h1>Workers</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
