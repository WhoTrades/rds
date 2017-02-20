<?php
/** @var $dataProvider yii\data\ActiveDataProvider */

$this->params['menu']=array(
	array('label'=>'Create Worker', 'url'=>array('create')),
	array('label'=>'Manage Worker', 'url'=>array('admin')),
);
?>

<h1>Workers</h1>

<?= \yii\widgets\ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => '_view',
]);
