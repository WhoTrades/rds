<?php
/* @var $this MaintenanceToolController */
/* @var $model MaintenanceTool */

$this->breadcrumbs=array(
    'Maintenance Tools',
);
$this->pageTitle = "Обслуживание";
$this->widget('yiistrap.widgets.TbGridView', array(
    'dataProvider'=>$model->search(),
    'rowCssClassExpression' => function($index, MaintenanceTool $tool){
        return 'maintenance-tool-'.$tool->obj_id;
    },
    'filter'=>$model,
    'columns'=>require('_maintenanceToolRow.php'),
)); ?>


<script>
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
</script>