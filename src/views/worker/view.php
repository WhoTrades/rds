<?php
/** @var $model app\models\Worker */

$this->params['menu']=array(
	array('label'=>'List Worker', 'url'=>array('index')),
	array('label'=>'Create Worker', 'url'=>array('create')),
	array('label'=>'Update Worker', 'url'=>array('update', 'id'=>$model->obj_id)),
	array('label'=>'Delete Worker', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->obj_id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Worker', 'url'=>array('admin')),
);
?>

<h1>View Worker #<?php echo $model->obj_id; ?></h1>

<?= yii\widgets\DetailView::widget([
    'model' => $model,
    'attributes' => [
        'obj_id',
        'obj_created',
        'obj_modified',
        'obj_status_did',
        'worker_name',
    ],
]);