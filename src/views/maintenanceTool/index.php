<?php
/* @var $this MaintenanceToolController */
/* @var $model MaintenanceTool */

$this->breadcrumbs=array(
    'Maintenance Tools',
);
$this->pageTitle = "Обслуживание";
$this->widget('bootstrap.widgets.TbGridView', array(
    'dataProvider'=>$model->search(),
    'rowCssClassExpression' => function($index, MaintenanceTool $tool){
        return 'maintenance-tool-'.$tool->obj_id;
    },
    'filter'=>$model,
    'columns'=>require('_maintenanceToolRow.php'),
)); ?>


<script>
    realplexor.subscribe('maintenanceToolChanged', function(event){
        console.log('Maintenance tool '+event.id+' updated');
        var html = event.html;
        console.log(html);
        var trHtmlCode = $(html).find('tr.rowItem').first().html()
        $('.maintenance-tool-'+event.id).html(trHtmlCode);
    });
    realplexor.subscribe('maintenanceToolProgressbarChanged', function(event){
        $('.progress-'+event.id+' .bar').css({width: event.percent+'%'});
        var html = '<b>'+(event.percent.toFixed(2).toString())+'%:</b> '+(event.key);
        $('.progress-'+event.id+' .bar').html(html);
        $('.progress-action-'+event.id).html(event.key);
    });
    realplexor.execute();
</script>