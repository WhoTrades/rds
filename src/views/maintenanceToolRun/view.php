<?php
/** @var $model MaintenanceToolRun */
$this->breadcrumbs=array(
    'Maintenance Tool Runs'=>array('index'),
    $model->obj_id,
);

$this->pageTitle = "Выполнение процесса ".$model->mtrMaintenanceTool->mt_name;

$this->menu=array(
    array('label'=>'List MaintenanceToolRun','url'=>array('index')),
    array('label'=>'Create MaintenanceToolRun','url'=>array('create')),
    array('label'=>'Update MaintenanceToolRun','url'=>array('update','id'=>$model->obj_id)),
    array('label'=>'Delete MaintenanceToolRun','url'=>'#','linkOptions'=>array('submit'=>array('delete','id'=>$model->obj_id),'confirm'=>'Are you sure you want to delete this item?')),
    array('label'=>'Manage MaintenanceToolRun','url'=>array('admin')),
);
?>

    <h1>View MaintenanceToolRun #<?php echo $model->obj_id; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView',array(
    'data'=>$model,
    'attributes'=>array(
        'obj_id',
        'obj_created',
        'obj_modified',
        'obj_status_did',
        'mtr_maintenance_tool_obj_id',
        'mtr_runner_user',
        'mtr_pid',
        'mtr_status',
        [
            'name' => 'progress',
            'value' => function(MaintenanceToolRun $toolRun){
                if (!$toolRun->isInProgress()) {
                    if ($toolRun->mtr_status == MaintenanceToolRun::STATUS_DONE) {
                        return '100%';
                    }
                    return;
                }

                list($percent, $key) = $toolRun->getProgressPercentAndKey();
                return '<div class="progress progress-'.$toolRun->obj_id.'" style="margin: 0; width: 250px;">
                            <div class="bar" role="progressbar"style="width: '.(int)$percent.'%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                                <b>'.sprintf("%.2f", $percent).'%</b>: '.$key.'
                            </div>
                        </div>';
            },
            'type' => 'html',
        ],
        [
            'name' => 'mtr_log',
            'value' => function(MaintenanceToolRun $mtr){
                return "<pre class='pre'>$mtr->mtr_log</pre>";
            },
            'type' => 'html',
        ]
    ),
)); ?>

<script type="text/javascript">
    realplexor.subscribe('maintenanceToolProgressbarChanged', function(event){
        console.log(event);
        $('.progress-'+event.id+' .bar').css({width: event.percent+'%'});
        var html = '<b>'+(event.percent.toFixed(2).toString())+'%:</b> '+(event.key);
        $('.progress-'+event.id+' .bar').html(html);
        $('.progress-action-'+event.id).html(event.key);
    });

    realplexor.subscribe('maintenance_tool_log_<?=$model->obj_id?>', function(event){
        $('.pre').append('<span>' + event.text + '</span>');
        var span = $('.pre span:last');
        span.css({fontWeight: 'bold'});
        setTimeout(function(){
            span.css({fontWeight: 'normal'});
        }, 250);
        $('body').scrollTop($('body').height())
    });
    realplexor.execute();
    $('body').scrollTop($('body').height());
</script>
