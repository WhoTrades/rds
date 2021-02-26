<?php
/** @var $model whotrades\rds\models\Project */
/** @var $dataProvider yii\data\ActiveDataProvider */
/** @var $searchModel ProjectConfigHistory */

use whotrades\rds\models\ProjectConfigHistory;
use yii\helpers\Url;

$this->params['menu'] = array(
    array('label' => Yii::t('rds', 'head_project_management'), 'url' => array('admin')),
    array('label' => Yii::t('rds', 'btn_create_project'), 'url' => array('create')),
    array('label' => Yii::t('rds', 'btn_update_project'), 'url' => array('update', 'id' => $model->obj_id)),
    ['label' => Yii::t('rds', 'project_build'), 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-build']],
    ['label' => Yii::t('rds', 'project_deploy_activation'), 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-deploy']],
    ['label' => Yii::t('rds', 'project_builds_removal'), 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-remove']],
    ['label' => Yii::t('rds', 'migrations'), 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-migration']],
    ['label' => Yii::t('rds', 'local_configuration'), 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-config-local']],
    ['label' => Yii::t('rds', 'cron_configuration'), 'url' => ['/project/update-script/', 'id' => $model->obj_id, 'type' => 'update-script-cron']],
    array(
        'label' => Yii::t('rds', 'btn_delete_project'),
        'url' => '#',
        'linkOptions' => array('submit' => array('delete', 'id' => $model->obj_id), 'confirm' => 'Are you sure you want to delete this item?'),
    ),
);
?>

<h1><?=Yii::t('rds', 'head_project_view', $model->obj_id)?></h1>

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
