<?php
/**
 * @var $model whotrades\rds\models\Log
 */
use kartik\grid\GridView;
?>

<?php
\yii\widgets\Pjax::begin(['id' => 'log-grid-pjax-container']);
echo GridView::widget(array(
    'id' => 'log-grid',
    'export' => false,
    'dataProvider' => $model->search($model->attributes),
    'filterModel' => $model,
    'columns' => array(
        'obj_created:datetime',
        'user.email',
        'log_text:html',
    ),
));
\yii\widgets\Pjax::end();
?>

<script>
    webSocketSubscribe('logUpdated', function (event) {
        console.log("websocket event received", event);
        $.pjax.reload('#log-grid-pjax-container');
    });
</script>
