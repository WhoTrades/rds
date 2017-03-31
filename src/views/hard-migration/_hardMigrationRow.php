<?php
/** @var $model app\models\HardMigration */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\BaseHtml;
use app\models\HardMigration;

return [
    [
        'class' => 'yii\grid\ActionColumn',
        'template' => '{warning} {start} {stop} {pause} {restart} {resume}',
        'visibleButtons' => [
            'start' => function ($model, $key, $index) {
                return $model->canBeStarted();
            },
            'stop' => function ($model, $key, $index) {
                return $model->canBeStopped();
            },
            'pause' => function ($model, $key, $index) {
                return $model->canBePaused();
            },
            'resume' => function ($model, $key, $index) {
                return $model->canBeResumed();
            },
            'restart' => function ($model, $key, $index) {
                return $model->canBeRestarted();
            },
            'warning' => function ($model, $key, $index) {
                return !$model->doesMigrationReleased();
            },
        ],
        'buttons' => [
            'start' => function ($url, $model, $key) {
                $icon   = BaseHtml::icon('play');
                $url    = Url::to(['/hard-migration/start', 'id' => $model->obj_id]);

                return Html::a($icon, $url, ['title' => 'Запустить миграцию']);
            },
            'stop' => function ($url, $model, $key) {
                $icon   = BaseHtml::icon('stop');
                $url    = Url::to(['/hard-migration/stop', 'id' => $model->obj_id]);

                return Html::a($icon, $url, ['title' => 'Остановить миграцию']);
            },
            'pause' => function ($url, $model, $key) {
                $icon   = BaseHtml::icon('pause');
                $url    = Url::to(['/hard-migration/pause', 'id' => $model->obj_id]);

                return Html::a($icon, $url, ['title' => 'Поставить на паузу']);
            },
            'resume' => function ($url, $model, $key) {
                $icon   = BaseHtml::icon('play');
                $url    = Url::to(['/hard-migration/resume', 'id' => $model->obj_id]);

                return Html::a($icon, $url, ['title' => 'Запустить миграцию']);
            },
            'restart' => function ($url, $model, $key) {
                $icon   = BaseHtml::icon('play');
                $url    = Url::to(['/hard-migration/restart', 'id' => $model->obj_id]);

                return Html::a($icon, $url, ['title' => 'Перезапустить миграцию']);
            },
            'warning' => function ($url, $model, $key) {
                $icon   = BaseHtml::icon('lock', ['style' => 'color: black; cursor: default']);

                return Html::tag('span', $icon, ['title' => 'Проект ещё не выложен, миграции пока нет на серверах, потому её нельзя накатить']);
            },
        ],
    ],
    [
        'attribute' => 'migration_progress',
        'value' => function (HardMigration $migration) {
            if (!in_array($migration->migration_status, [HardMigration::MIGRATION_STATUS_IN_PROGRESS, HardMigration::MIGRATION_STATUS_PAUSED])) {
                return false;
            }

            return '<div class="progress progress-' . str_replace("/", "", $migration->migration_name) . '_' . $migration->migration_environment . '" style="margin:0;width:250px;">
                        <div class="progress-bar" style="width: ' . $migration->migration_progress . '%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                            <b>' . sprintf("%.2f", $migration->migration_progress) . '%:</b> ' . $migration->migration_progress_action . '
                        </div>
                    </div>';
        },
        'format' => 'html',
    ],
    [
        'attribute' => 'migration_status',
        'value' => function (HardMigration $migration) {
            $map = array(
                HardMigration::MIGRATION_STATUS_NEW => array('time', 'Миграция ожидает запуска, ручного или автоматического', 'black'),
                HardMigration::MIGRATION_STATUS_IN_PROGRESS => array('refresh', 'Миграция выполняется', 'orange'),
                HardMigration::MIGRATION_STATUS_STARTED => array('refresh', 'Отправлен сигнал на запуск миграции, ожидаем пока скрипты отработают и миграция запустится', 'orange'),
                HardMigration::MIGRATION_STATUS_DONE => array('ok', 'Миграция была успешно выполнена', '#32cd32'),
                HardMigration::MIGRATION_STATUS_FAILED => array('ban-circle', 'Миграция была запущена и заверилась с ошибкой', 'red'),
                HardMigration::MIGRATION_STATUS_PAUSED => array('time', 'Миграция была поставлена на паузу и может быть в любой момент снова запущена', 'blue'),
                HardMigration::MIGRATION_STATUS_STOPPED => array('ban-circle', 'Миграция была остановлена отправкой сигнала KILL', 'red'),
            );

            list($icon, $text, $color) = $map[$migration->migration_status];

            $text = "<span title='{$text}' style='color: $color; font-weight: bold'>" .
                BaseHtml::icon($icon, ['style' => 'margin-right: 1px;']) . "$migration->migration_status</span><br />";
            if ($migration->migration_log) {
                $text .= "<a href='" . Url::to(['/hard-migration/log', 'id' => $migration->obj_id]) . "'>LOG</a>";
            }

            return $text;
        },
        'filter' => array_combine(HardMigration::getAllStatuses(), HardMigration::getAllStatuses()),
        'format' => 'html',
    ],
    [
        'attribute' => 'migration_name',
        'value' => function (HardMigration $migration) {
            $migrationUrl = $migration->project->getMigrationUrl($migration->migration_name, 'hard', $migration->releaseRequest->rr_build_version);

            return "<a href='{$migrationUrl}' title='Посмотреть исходный код миграции'>{$migration->migration_name}</a><br />";
        },
        'format' => 'html',
    ],
    [
        'attribute' => 'migration_ticket',
        'value' => function (HardMigration $migration) {
            return "<a href='https://jira.finam.ru/browse/$migration->migration_ticket' target='_blank' title='Перейти в JIRA'>$migration->migration_ticket</a>";
        },
        'format' => 'html',
    ],
    'migration_retry_count',
    [
        'attribute' => 'migration_progress_action',
        'value' => function (HardMigration $migration) {
            return "<div class='progress-action-$migration->obj_id'>$migration->migration_progress_action</div>";
        },
        'format' => 'html',
    ],
    [
        'attribute' => 'releaseRequest.rr_build_version',
        'filter' => Html::activeTextInput($model, 'build_version'),
    ],
    'obj_created',
    'migration_release_request_obj_id',
];
