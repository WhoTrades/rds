<?php
/** @var $model app\models\Build */
use yii\helpers\Html;

$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#build-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
", $this::POS_READY, 'search');
?>

<h1>Manage Builds</h1>

<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo Html::a('Advanced Search', '#', array('class' => 'search-button')); ?>
<div class="search-form" style="display:none">
<?php echo $this->render('_search', array(
    'model' => $model,
)); ?>
</div><!-- search-form -->

<?= yii\grid\GridView::widget(array(
    'id' => 'build-grid',
    'dataProvider' => $model->search($model->attributes),
    'filterModel' => $model,
    'options' => ['class' => 'table-responsive'],
    'columns' => [
        'obj_id',
        'obj_created:datetime',
        'project.project_name',
        'worker.worker_name',
        'build_status',
        'build_attach',
        'build_version',
        [
            'class' => 'yii\grid\ActionColumn',
        ],
    ],
));
