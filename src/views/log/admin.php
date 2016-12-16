
<?php
/* @var $this LogController */
/* @var $model Log */

$this->breadcrumbs=array(
    'Logs'=>array('index'),
    'Manage',
);

Yii::app()->clientScript->registerScript('search', "
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
");
?>

<h1>Logs</h1>

<p>
    You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
    or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo CHtml::link('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php $this->renderPartial('_search',array(
    'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('yiistrap.widgets.TbGridView', array(
    'id'=>'log-grid',
    'dataProvider'=>$model->search(),
    'htmlOptions' => ['class' => 'table-responsive'],
    'filter'=>$model,
    'columns'=>array(
        'obj_created',
        'log_user',
        [
            'name' => 'log_text',
            'type' => 'html',
        ],
        'obj_id',
    ),
)); ?>

<script>
    webSocketSubscribe('logUpdated', function(event){
        console.log("websocket event received", event);
        $.fn.yiiGridView.update("log-grid");
    });
</script>
