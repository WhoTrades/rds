<?php
/**
 * @var $this yii\web\View
 * @var $dataProvider yii\data\BaseDataProvider
 * @var $filterModel yii\base\Model | null
 */

use yii\grid\GridView;

echo GridView::widget([
    'id'            => 'release-request-grid',
    'options'       => ['class' => 'table-responsive'],
    'dataProvider'  => $dataProvider,
    'filterModel'   => $filterModel ?? null,
    'rowOptions'    => function ($model) {
        return [
            'class' => 'release-request-' . $model->obj_id . ' release-request-' . $model->rr_status,
        ];
    },
    'columns' => include('_releaseRequestRow.php'),
]);
