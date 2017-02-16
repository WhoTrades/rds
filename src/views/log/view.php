<?php

/* @var $model Log */

$this->params['menu']=array(
    array('label'=>'List Log', 'url'=>array('index')),
    array('label'=>'Create Log', 'url'=>array('create')),
    array('label'=>'Update Log', 'url'=>array('update', 'id'=>$model->obj_id)),
    array('label'=>'Delete Log', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->obj_id),'confirm'=>'Are you sure you want to delete this item?')),
    array('label'=>'Manage Log', 'url'=>array('admin')),
);
?>

<h1>View Log #<?php echo $model->obj_id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
    'data'=>$model,
    'attributes'=>array(
        'obj_id',
        'obj_created',
        'obj_modified',
        'obj_status_did',
        'log_user',
        'log_text',
    ),
)); ?>