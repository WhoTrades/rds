<?php
/**
 * @var $model whotrades\rds\models\Project
 * @var $workers whotrades\rds\models\Worker[]
 */
use whotrades\rds\models\Project;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->params['menu'] = array(
    array('label' => Yii::t('rds', 'btn_create_project'), 'url' => array('create')),
);
$this->registerJs('$(function () {$(\'[data-toggle="tooltip"]\').tooltip()})');
?>
<h1><?=Yii::t('rds', 'head_project_management')?></h1>
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
            'header' => Yii::t('rds', 'worker'),
        ],
        [
            'value' => function (Project $project) {
                $data = [
                    Yii::t('rds', 'project_build') => [
                        'url' => ['/project/update-script/', 'id' => $project->obj_id, 'type' => 'update-script-build'],
                        'hint' => Yii::t('rds', 'hint_project_build_script'),
                    ],
                    Yii::t('rds', 'project_deploy_activation') => [
                        'url' => ['/project/update-script/', 'id' => $project->obj_id, 'type' => 'update-script-deploy'],
                        'hint' => Yii::t('rds', 'hint_project_deploy_activation'),
                    ],
                    Yii::t('rds', 'project_builds_removal') => [
                        'url' => ['/project/update-script/', 'id' => $project->obj_id, 'type' => 'update-script-remove'],
                        'hint' => Yii::t('rds', 'hint_project_builds_removal'),
                    ],
                    Yii::t('rds', 'migrations') => [
                        'url' => ['/project/update-script/', 'id' => $project->obj_id, 'type' => 'update-script-migration'],
                        'hint' => Yii::t('rds', 'hint_migrations'),
                    ],
                    Yii::t('rds', 'local_configuration') => [
                        'url' => ['/project/update-script/', 'id' => $project->obj_id, 'type' => 'update-config-local'],
                        'hint' => Yii::t('rds', 'hint_local_configuration'),
                    ],
                    Yii::t('rds', 'cron_configuration') => [
                        'url' => ['/project/update-script/', 'id' => $project->obj_id, 'type' => 'update-script-cron'],
                        'hint' => Yii::t('rds', 'hint_cron_configuration'),
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
            'header' => Yii::t('rds', 'build_scripts'),
        ],
        [
            'class' => yii\grid\ActionColumn::class,
        ],
    ],
));
