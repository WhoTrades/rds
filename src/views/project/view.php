<?php
/** @var $model app\models\Project */

use yii\helpers\Url;

$this->params['menu'] = array(
    array('label' => 'Create Project', 'url' => array('create')),
    array('label' => 'Update Project', 'url' => array('update', 'id' => $model->obj_id)),
    array(
        'label' => 'Delete Project',
        'url' => '#',
        'linkOptions' => array('submit' => array('delete', 'id' => $model->obj_id), 'confirm' => 'Are you sure you want to delete this item?'),
    ),
    array('label' => 'Manage Project', 'url' => array('admin')),
);
?>

<h1>View Project #<?php echo $model->obj_id; ?></h1>

<?= yii\widgets\DetailView::widget([
    'model' => $model,
    'attributes' => [
        'obj_id',
        'obj_created',
        'obj_modified',
        'obj_status_did',
        'project_name',
    ],
]);

echo yii\grid\GridView::widget([
    'id' => 'log-grid',
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'options' => ['class' => 'table-responsive'],
    'columns' => [
        'obj_created',
        'pch_user',
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view}',
            'urlCreator' => function ($action, $model) {
                return Url::to(['/diff/project_config', 'id' => $model->obj_id]);
            },
        ],
    ],
]);
