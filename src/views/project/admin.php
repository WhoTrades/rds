<?php
/* @var $model Project */
/* @var $workers Worker[] */

$this->params['menu']=array(
	array('label'=>'Create Project', 'url'=>array('create')),
);

\Yii::$app->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#project-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1>Manage Projects</h1>

<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo CHtml::link('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php echo $this->render('_search',array(
	'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('yiistrap.widgets.TbGridView', array(
	'id'=>'project-grid',
	'dataProvider'=>$model->search(),
	'htmlOptions' => ['class' => 'table-responsive'],
	'filter'=>$model,
	'columns'=>array(
		'project_name',
		'project_current_version',
		'project_notification_email',
		array(
            'value' => function($list) use ($workers) {
                $result = array();
                foreach ($list->project2workers as $p2w) {
                    foreach ($workers as $worker) {
                        if ($worker->obj_id==$p2w->worker_obj_id) {
                            $result[] = $worker->worker_name;
                        }
                    }
                }

                return implode(", ", $result);
            },
            'filter' => 'Workers',
        ),
		array(
            'class'=>'yiistrap.widgets.TbButtonColumn',
		),
	),
)); ?>
