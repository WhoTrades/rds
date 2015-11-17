<?php
/* @var $this ProjectController */
/* @var $model Project */

$this->breadcrumbs=array(
	'Projects'=>array('index'),
	$model->obj_id,
);

$this->menu=array(
	array('label'=>'Create Project', 'url'=>array('create')),
	array('label'=>'Update Project', 'url'=>array('update', 'id'=>$model->obj_id)),
	array('label'=>'Delete Project', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->obj_id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Project', 'url'=>array('admin')),
);
?>

<h1>View Project #<?php echo $model->obj_id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'obj_id',
		'obj_created',
		'obj_modified',
		'obj_status_did',
		'project_name',
	),
)); ?>


<?php $this->widget('yiistrap.widgets.TbGridView', array(
    'id'=>'log-grid',
    'dataProvider'=>$configHistoryModel->search(),
    'filter'=>$configHistoryModel,
    'columns'=>array(
        'obj_created',
        'pch_user',
        array(
            'class'=>'yiistrap.widgets.TbButtonColumn',
            'template' => '{view}',
            'viewButtonUrl' => 'Yii::app()->controller->createUrl("/diff/project_config",array("id"=>$data->primaryKey))',
        ),
    ),
)); ?>