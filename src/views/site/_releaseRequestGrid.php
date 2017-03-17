<?php
/**
 * @var $this yii\web\View
 * @var $dataProvider yii\data\BaseDataProvider
 * @var $filterModel yii\base\Model | null
 */

use \kartik\grid\GridView;
//use yii\grid\GridView;

echo GridView::widget([
    'id'           => 'release-request-grid',
    'dataProvider' => $dataProvider,
    'filterModel'  => $filterModel,
    'columns'      => include('_releaseRequestRow.php'),
    'responsive'   => true,
    'hover'        => true,
    'export'       => false,
    'rowOptions' => function ($rr, $key, $index) {
        return ['class' => 'release-request-' . $rr->obj_id . " release-request-" . $rr->rr_status];
    },
]);
