<?php
/**
 * @var $this yii\web\View
 * @var $dataProvider yii\data\BaseDataProvider
 * @var $filterModel yii\base\Model | null
 */

use whotrades\rds\models\ReleaseRequest;
use yii\grid\GridView;

echo GridView::widget([
    'id'           => 'release-request-grid',
    'dataProvider' => $dataProvider,
    'filterModel'  => $filterModel,
    'columns'      => include('_releaseRequestRow.php'),
    'rowOptions' => function (ReleaseRequest $rr, $key, $index) {
        return [
            'class' => 'release-request-' . $rr->obj_id . " release-request-" . $rr->rr_status . " " . ($rr->isDeleted() ? "release-request-deleted" : ""),
        ];
    },
]);
