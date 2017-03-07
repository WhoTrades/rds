<?php
/**
 * @var $this yii\web\View
 * @var $dataProvider yii\data\BaseDataProvider
 * @var $filterModel yii\base\Model | null
 */

echo yii\grid\GridView::widget([
    'id'            => 'hard-migration-grid',
    'dataProvider'  => $dataProvider,
    'filterModel'   => $filterModel ?? null,
    'options'       => ['class' => 'table-responsive'],
    'rowOptions'    => function ($model, $key, $index, $grid) {
        return [
            'class' => 'hard-migration-' . str_replace("/", "", $model->migration_name) . '_' . $model->migration_environment,
        ];
    },
    'columns' => include('_hardMigrationRow.php'),
]);
