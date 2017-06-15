<?php
/**
 * @var $model app\models\Project
 * @var $workers app\models\Worker[]
 */
use app\models\Project;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->params['menu'] = array(
    array('label' => 'Создать проект', 'url' => array('create')),
);
$this->registerJs('$(function () {$(\'[data-toggle="tooltip"]\').tooltip()})');
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
            'value' => function (Project $project) {
                $data = [
                    'Миграции' => [
                        'url' => ['/project/update-script-migration/', 'id' => $project->obj_id],
                        'hint' => 'Настройка выполнения SQL миграций: команда для получения списка новых миграций и для запуска миграции',
                    ],
                    'Локальная настройка' => [
                        'url' => ['/project/update-config-local/', 'id' => $project->obj_id],
                        'hint' => 'Управление настройками окружения (то, что не лежит в git и чем отличается dev и prod контура)',
                    ],
                    '[todo] Сборка проекта' => [
                        'url' => ['#', 'id' => $project->obj_id],
                        'hint' => 'В простейшем случае сдесь находится просто git clone',
                        'not-ready' => true,
                    ],
                    '[todo] Заливка проекта на сервера' => [
                        'url' => ['#', 'id' => $project->obj_id],
                        'hint' => 'Непосредственно скрипты заливки на сервера. Как правило, тут находится rsync',
                        'not-ready' => true,
                    ],
                    '[todo] Удаление проекта' => [
                        'url' => ['#', 'id' => $project->obj_id],
                        'hint' => 'Скрипты удаления старых версий проекта',
                        'not-ready' => true,
                    ],
                    '[todo] cron конфиги' => [
                        'url' => ['#', 'id' => $project->obj_id],
                        'hint' => 'Скрипты генерации cron конфигов',
                        'not-ready' => true,
                    ],
                ];
                $links = [];
                foreach ($data as $key => $val) {
                    $links[] = Html::a($key, Url::to($val['url'])) . " " .
                        Html::a("<span class='glyphicon glyphicon-info-sign'></span>", "#", [
                            'data-toggle' => 'tooltip',
                            'style' => 'color: orange',
                            'data-original-title' => $val['hint'],
                        ]);
                }

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
