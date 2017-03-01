<?php

use yii\bootstrap\Html;

return [
    'mt_environment',
    'mt_name',
    'mt_command',
    [
        'value' => function ($data) {
            return $data->lastRun ? Html::a($data->lastRun->mtr_status, ['/maintenance-tool-run/view', 'id' => $data->lastRun->obj_id]) : Html::tag('i', 'never runed');
        },
        'format' => 'html',
    ],
    [
        'value' => function ($data) {
            $toolRun = $data->lastRun;

            if (empty($toolRun) || !$toolRun->isInProgress()) {
                return;
            }

            list($percent, $key) = $toolRun->getProgressPercentAndKey();

            return '<div class="progress progress-' . $toolRun->obj_id . '" style="margin: 0; width: 250px;">
                        <div class="progress-bar" style="width: ' . (int) $percent . '%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                            <b>' . sprintf("%.2f", $percent) . '%</b>: ' . $key . '
                        </div>
                    </div>';
        },
        'header' => 'Last run',
        'format' => 'html',
    ],
    [
        'value' => function ($data) {
            return Html::a('view all runs', ['/maintenance-tool-run/index', 'MaintenanceToolRun[mtr_maintenance_tool_obj_id]' => $data->obj_id]);
        },
        'header' => 'Run log',
        'format' => 'html',
    ],
    [
        'class' => 'yii\grid\ActionColumn',
        'template' => '{start} {stop}',
        'visibleButtons' => [
            'start' => function ($model, $key, $index) {
                return $model->canBeStarted();
            },
            'stop' => function ($model, $key, $index) {
                return $model->canBeKilled();
            },
        ],
        'buttons' => [
            'start' => function ($url, $model, $key) {
                return Html::a(Html::icon('play', ['style' => 'color: #32cd32']), ['/maintenance-tool/start', 'id' => $model->primaryKey], ['title' => 'Запустить команду']);
            },
            'stop' => function ($url, $model, $key) {
                return Html::a(Html::icon('stop', ['style' => 'color: red']), ['/maintenance-tool/stop', 'id' => $model->primaryKey], ['title' => 'Остановить команду']);
            },
        ],
    ],
];
