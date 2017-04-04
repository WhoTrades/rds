<?php

$this->params['menu'] = array(
    array('label' => 'List MaintenanceToolRun', 'url' => array('index')),
    array('label' => 'Create MaintenanceToolRun', 'url' => array('create')),
);

?>

<h1>Manage Maintenance Tool Runs</h1>

<p>
    You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
    or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php
echo yii\grid\GridView::widget([
    'id' => 'maintenance-tool-run-grid',
    'dataProvider' => $model->search($model->attributes),
    'filterModel' => $model,
    'options' => ['class' => 'table-responsive'],
    'columns' => [
        'obj_id',
        'obj_created',
        'mtrMaintenanceTool.mt_name',
        'mtr_runner_user',
        'mtr_pid',
        'mtr_status',
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view}',
        ],
    ],
]);
