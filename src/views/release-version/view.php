<?php
/** @var $model whotrades\rds\models\ReleaseVersion */

$this->params['menu'] = array(
    array('label' => 'List ReleaseVersion', 'url' => array('index')),
    array('label' => 'Create ReleaseVersion', 'url' => array('create')),
    array('label' => 'Update ReleaseVersion', 'url' => array('update', 'id' => $model->obj_id)),
    array(
        'label' => 'Delete ReleaseVersion',
        'url' => '#',
        'linkOptions' => array('submit' => array('delete', 'id' => $model->obj_id), 'confirm' => 'Are you sure you want to delete this item?'),
    ),
    array('label' => 'Manage ReleaseVersion', 'url' => array('admin')),
);
?>

<h1>View ReleaseVersion #<?php echo $model->obj_id; ?></h1>

<?= yii\widgets\DetailView::widget([
    'model' => $model,
    'attributes' => [
        'obj_id',
        'obj_created',
        'obj_modified',
        'obj_status_did',
        'rv_version',
        'rv_name',
    ],
]);