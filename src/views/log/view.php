<?php
/** @var $model Log */

use whotrades\rds\models\Log;

$this->params['menu'] = array(
    array('label' => 'List Log', 'url' => array('index')),
    array('label' => 'Create Log', 'url' => array('create')),
    array('label' => 'Update Log', 'url' => array('update', 'id' => $model->obj_id)),
    array('label' => 'Manage Log', 'url' => array('admin')),
);
?>

<h1>View Log #<?php echo $model->obj_id; ?></h1>

<?php
echo $this->widget('zii.widgets.CDetailView', array(
    'data' => $model,
    'attributes' => array(
        'obj_id',
        'obj_created:datetime',
        'obj_modified',
        'obj_status_did',
        'user.email',
        'log_text',
    ),
));