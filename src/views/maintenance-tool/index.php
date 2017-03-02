<?php
/** @var $model app\models\MaintenanceTool */

$this->title = "Обслуживание";

echo $this->render('_maintenanceToolGrid', ['dataProvider' => $model->search($model->attributes), 'filterModel' => $model]);
?>


<script>
    document.onload.push(function(){
        webSocketSubscribe('maintenanceToolChanged', function(event){
            console.log('Maintenance tool '+event.id+' updated');

            var trId = '.maintenance-tool-' + event.id,
                html = $(event.html).find(trId).html();

            $(trId).html(html);
        });
        webSocketSubscribe('maintenanceToolProgressbarChanged', function(event){
            $('.progress-'+event.id+' .progress-bar').css({width: event.percent+'%'});
            var html = '<b>'+(event.percent.toFixed(2).toString())+'%:</b> '+(event.key);
            $('.progress-'+event.id+' .progress-bar').html(html);
            $('.progress-action-'+event.id).html(event.key);
        });
    })
</script>