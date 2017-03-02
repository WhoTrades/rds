<?php
/**
 * @var $this yii\web\View
 * @var $dataProvider yii\data\BaseDataProvider
 * @var $filterModel yii\base\Model | null
 */

echo yii\grid\GridView::widget([
    'dataProvider'  => $dataProvider,
    'filterModel'   => $filterModel ?? null,
    'options'       => ['class' => 'table-responsive'],
    'rowOptions'    => function ($model) {
        return ['class' => 'maintenance-tool-' . $model->obj_id];
    },
    'columns' => require('_maintenanceToolRow.php'),
]);
