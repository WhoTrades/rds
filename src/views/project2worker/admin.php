<?php
/* @var $this Project2workerController */
/* @var $model Project2worker */

$this->breadcrumbs=array(
	'Project2workers'=>array('index'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Project2worker', 'url'=>array('index')),
	array('label'=>'Create Project2worker', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#project2worker-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1>Manage Project2workers</h1>

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
	'id'=>'project2worker-grid',
	'dataProvider'=>$model->search(),
	'htmlOptions' => ['class' => 'table-responsive'],
	'filter'=>$model,
	'columns'=>array(
		'obj_id',
		'obj_created',
		'obj_modified',
		'obj_status_did',
		'worker.worker_name',
		'project.project_name',
		array(
            'class'=>'yiistrap.widgets.TbButtonColumn',
		),
	),
)); ?>
