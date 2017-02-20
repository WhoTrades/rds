<?php
/**
 * @var $model app\models\Project
 * @var $model app\models\Worker[]
 */

use yii\helpers\Html;

$this->params['menu']=array(
	array('label'=>'Create Project', 'url'=>array('create')),
);

$this->registerJs("
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
", $this::POS_READY, 'search');
?>

<h1>Manage Projects</h1>

<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo Html::a('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php echo $this->render('_search',array(
	'model'=>$model,
)); ?>
</div><!-- search-form -->

<?= yii\grid\GridView::widget(array(
    'id' => 'project-grid',
    'dataProvider' => $model->search($model->attributes),
    'options' => ['class' => 'table-responsive'],
    'filterModel' => $model,
    'columns' => [
        'project_name',
        'project_current_version',
        'project_notification_email',
        [
            'value' => function ($list) use ($workers) {
                $result = array();
                foreach ($list->project2workers as $p2w) {
                    foreach ($workers as $worker) {
                        if ($worker->obj_id == $p2w->worker_obj_id) {
                            $result[] = $worker->worker_name;
                        }
                    }
                }

                return implode(", ", $result);
            },
        ],
        [
            'class' => 'yii\grid\ActionColumn',
        ],
    ]
));
