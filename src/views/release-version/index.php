<?php
/** @var $dataProvider yii\data\ActiveDataProvider */

$this->params['menu']=array(
	array('label'=>'Create ReleaseVersion', 'url'=>array('create')),
	array('label'=>'Manage ReleaseVersion', 'url'=>array('admin')),
);
?>

<h1>Release Versions</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
