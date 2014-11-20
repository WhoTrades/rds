<?php
$this->breadcrumbs=array(
    'Maintenance Tool Runs'=>array('index'),
    'Manage',
);

$this->menu=array(
    array('label'=>'List MaintenanceToolRun','url'=>array('index')),
    array('label'=>'Create MaintenanceToolRun','url'=>array('create')),
);

?>

<h1>Manage Maintenance Tool Runs</h1>

<p>
    You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
    or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php $this->widget('yiistrap.widgets.TbGridView',array(
    'id'=>'maintenance-tool-run-grid',
    'dataProvider'=>$model->search(),
    'filter'=>$model,
    'columns'=>array(
        'obj_id',
        'obj_created',
        'mtrMaintenanceTool.mt_name',
        'mtr_runner_user',
        'mtr_pid',
        'mtr_status',
        array(
            'class'=>'yiistrap.widgets.TbButtonColumn',
            'template' => '{view}'
        ),
    ),
)); ?> 