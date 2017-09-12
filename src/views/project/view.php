<?php
/** @var $model app\models\Project */

use yii\helpers\Url;

$this->params['menu'] = array(
    array('label' => 'Create Project', 'url' => array('create')),
    array('label' => 'Update Project', 'url' => array('update', 'id' => $model->obj_id)),
    ['label' => 'Миграции', 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-migration']],
    ['label' => 'Локальная настройка', 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-config-local']],
    ['label' => 'Очистка пакетов', 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-remove']],
    ['label' => 'Заливка/активация проекта', 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-deploy']],
    ['label' => 'CRON конфиги', 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-cron']],
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
        'obj_created:datetime',
        'project_name',
        'project_servers',
    ],
]);

echo yii\grid\GridView::widget([
    'id' => 'log-grid',
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'options' => ['class' => 'table-responsive'],
    'columns' => [
        'obj_created:datetime',
        'user.email',
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view}',
            'urlCreator' => function ($action, $model) {
                return Url::to(['/diff/project_config', 'id' => $model->obj_id]);
            },
        ],
    ],
]);
