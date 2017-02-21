<?php
/** @var $model app\models\MaintenanceTool */

$this->title = "Обслуживание";

echo yii\grid\GridView::widget(array(
    'dataProvider' => $model->search($model->attributes),
    'options' => ['class' => 'table-responsive'],
    'filterModel' => $model,
    'rowOptions' => function ($model) {
        return ['class' => 'maintenance-tool-' . $model->obj_id];
    },
    'columns' => require('_maintenanceToolRow.php'),
)); ?>


<script>
    document.onload.push(function(){
        webSocketSubscribe('maintenanceToolChanged', function(event){
            console.log('Maintenance tool '+event.id+' updated');
            var html = event.html;
            console.log(html);
            var trHtmlCode = $(html).find('tr.rowItem').first().html()
            $('.maintenance-tool-'+event.id).html(trHtmlCode);
        });
        webSocketSubscribe('maintenanceToolProgressbarChanged', function(event){
            $('.progress-'+event.id+' .progress-bar').css({width: event.percent+'%'});
            var html = '<b>'+(event.percent.toFixed(2).toString())+'%:</b> '+(event.key);
            $('.progress-'+event.id+' .progress-bar').html(html);
            $('.progress-action-'+event.id).html(event.key);
        });
    })
</script>