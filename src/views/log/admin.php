<?php
/**
 * @var $model app\models\Log
 */

use yii\helpers\Html;

$this->registerJs("
$('.search-button').click(function(){
    $('.search-form').toggle();
    return false;
});
$('.search-form form').submit(function(){
    $('#log-grid').yiiGridView('update', {
        data: $(this).serialize()
    });
    return false;
});
", $this::POS_READY, 'search');
?>

<h1>Logs</h1>

<p>
    You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
    or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?= Html::a('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php echo $this->render('_search',array(
    'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php
\yii\widgets\Pjax::begin(['id' => 'log-grid-pjax-container']);
echo yii\grid\GridView::widget(array(
    'id' => 'log-grid',
    'dataProvider' => $model->search($model->attributes),
    'filterModel' => $model,
    'columns' => array(
        'obj_created',
        'log_user',
        [
            'attribute' => 'log_text',
            'format' => 'html',
        ],
        'obj_id',
    ),
));
\yii\widgets\Pjax::end();
?>

<script>
    webSocketSubscribe('logUpdated', function(event){
        console.log("websocket event received", event);
        $.pjax.reload('#log-grid-pjax-container');
    });
</script>
