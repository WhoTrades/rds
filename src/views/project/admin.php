<?php
/**
 * @var $model app\models\Project
 * @var $workers app\models\Worker[]
 */
use app\models\Project;
use app\models\Worker;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->params['menu'] = array(
    array('label' => 'Создать проект', 'url' => array('create')),
);
?>
<h1>Управление проектами</h1>
<?=GridView::widget(array(
    'id' => 'project-grid',
    'export' => false,
    'dataProvider' => $model->search($model->attributes),
    'options' => ['class' => 'table-responsive'],
    'filterModel' => $model,
    'columns' => [
        'project_name',
        'project_notification_email',
        [
            'value' => function (Project $project) use ($workers) {
                $result = array();
                foreach ($project->project2workers as $p2w) {
                    foreach ($workers as $worker) {
                        if ($worker->obj_id == $p2w->worker_obj_id) {
                            $result[] = $worker->worker_name;
                        }
                    }
                }

                return implode(", ", $result);
            },
            'header' => 'Сборщик',
        ],
        [
            'value' => function (Project $project){
                $links = [];
                $links[] = Html::a('Миграции', Url::to(['/project/update-script-migration', 'id' => $project->obj_id]));

                return implode("<br />", $links);
            },
            'format' => 'raw',
            'header' => 'Сборочные скрипты',
        ],
        [
            'class' => yii\grid\ActionColumn::class,
        ],
    ],
));
