<?php
/** @var $model HardMigration */
return array(
//    [
//        'name' => 'migration_environment',
//        'filter' => ['main' => 'main', 'preprod' => 'preprod'],
//    ],
//    [
//        'name' => 'migration_project_obj_id',
//        'value' => function(HardMigration $migration){
//            return $migration->project->project_name;
//        },
//        'filter' => Project::model()->forList(),
//    ],
    array(
        'class'=>'yiistrap.widgets.TbButtonColumn',
        'template' => '{warning} {start} {stop} {pause} {restart} {resume}',
        'buttons' => [
            'start' => [
                'visible' => '$data->canBeStarted()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/start",array("id"=>$data->primaryKey))',
                'label' => TbHtml::icon(TbHtml::ICON_PLAY, ['style' => 'color: #32cd32']),
                'options' => [
                    'title' => 'Запустить миграцию',
                ],
            ],
            'stop' => [
                'visible' => '$data->canBeStopped()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/stop",array("id"=>$data->primaryKey))',
                'label' => TbHtml::icon(TbHtml::ICON_STOP, ['style' => 'color: red']),
                'options' => [
                    'title' => 'Остановить миграцию',
                ],
            ],
            'pause' => [
                'visible' => '$data->canBePaused()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/pause",array("id"=>$data->primaryKey))',
                'label' => TbHtml::icon(TbHtml::ICON_PAUSE, ['style' => 'color: #32cd32']),
                'options' => [
                    'title' => 'Поставить на паузу',
                ],
            ],
            'resume' => [
                'visible' => '$data->canBeResumed()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/resume",array("id"=>$data->primaryKey))',
                'label' => TbHtml::icon(TbHtml::ICON_PLAY, ['style' => 'color: #32cd32']),
                'options' => [
                    'title' => 'Запустить миграцию',
                ],
            ],
            'restart' => [
                'visible' => '$data->canBeRestarted()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/restart",array("id"=>$data->primaryKey))',
                'label' => TbHtml::icon(TbHtml::ICON_PLAY, ['style' => 'color: #32cd32']),
                'options' => [
                    'title' => 'Перезапустить миграцию',
                ],
            ],
            'warning' => [
                'visible' => '!$data->doesMigrationReleased()',
                'label' => TbHtml::icon(TbHtml::ICON_LOCK, ['style' => 'color: black; cursor: default']),
                'options' => [
                    'title' => 'Проект ещё не выложен, миграции пока нет на серверах, потому её нельзя накатить',
                ],
            ],
        ],
    ),
    [
        'name' => 'migration_progress',
        'value' => function(HardMigration $migration){
            if (!in_array($migration->migration_status, [\HardMigration::MIGRATION_STATUS_IN_PROGRESS, \HardMigration::MIGRATION_STATUS_PAUSED])) {
                return false;
            }
            return '<div class="progress progress-'.str_replace("/", "", $migration->migration_name).'_'.$migration->migration_environment.'" style="margin: 0; width: 250px;">
                        <div class="progress-bar" style="width: '.$migration->migration_progress.'%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                            <b>'.sprintf("%.2f", $migration->migration_progress).'%:</b> '.$migration->migration_progress_action.'
                        </div>
                    </div>';
        },
        'type' => 'html',
    ],
    [
        'name' => 'migration_status',
        'value' => function(HardMigration $migration){
            $map = array(
                HardMigration::MIGRATION_STATUS_NEW => array(TbHtml::ICON_TIME, 'Миграция ожидает запуска, ручного или автоматического', 'black'),
                HardMigration::MIGRATION_STATUS_IN_PROGRESS => array(TbHtml::ICON_REFRESH, 'Миграция выполняется', 'orange'),
                HardMigration::MIGRATION_STATUS_STARTED => array(TbHtml::ICON_REFRESH, 'Отправлен сигнал на запуск миграции, ожидаем пока скрипты отработают и миграция запустится', 'orange'),
                HardMigration::MIGRATION_STATUS_DONE=> array(TbHtml::ICON_OK, 'Миграция была успешно выполнена', '#32cd32'),
                HardMigration::MIGRATION_STATUS_FAILED => array(TbHtml::ICON_BAN_CIRCLE, 'Миграция была запущена и заверилась с ошибкой', 'red'),
                HardMigration::MIGRATION_STATUS_PAUSED=> array(TbHtml::ICON_TIME, 'Миграция была поставлена на паузу и может быть в любой момент снова запущена', 'blue'),
                HardMigration::MIGRATION_STATUS_STOPPED => array(TbHtml::ICON_BAN_CIRCLE, 'Миграция была остановлена отправкой сигнала KILL', 'red'),
            );

            list($icon, $text, $color) = $map[$migration->migration_status];
            echo "<span title='{$text}' style='color: $color; font-weight: bold'>".
                TbHtml::icon($icon, ['style' => 'margin-right: 1px;']).
                "$migration->migration_status</span><br />";
            if ($migration->migration_log) {
                echo "<a href='" . Yii::app()->createAbsoluteUrl('/hardMigration/log', ['id' => $migration->obj_id]) . "'>LOG</a>";
            }
        },
        'filter' => array_combine(HardMigration::getAllStatuses(), HardMigration::getAllStatuses()),
        'type' => 'html',
    ],
    [
        'name' => 'migration_name',
        'value' => function (HardMigration $migration) {

            $text = "<a
                href='" . $migration->project->getMigrationUrl($migration->migration_name, 'hard') . "'
                target='_blank'
                title='Посмотреть исходный код миграции'
            >
                $migration->migration_name
            </a><br />";

            return $text;
        },
        'type' => 'html',
    ],
    [
        'name' => 'migration_ticket',
        'value' => function (HardMigration $migration) {
            return "<a href='https://jira.finam.ru/browse/$migration->migration_ticket' target='_blank' title='Перейти в JIRA'>$migration->migration_ticket</a>";
        },
        'type' => 'html',
    ],

    'migration_retry_count',

    [
        'name' => 'migration_progress_action',
        'value' => function (HardMigration $migration) {
            echo "<div class='progress-action-$migration->obj_id'>$migration->migration_progress_action</div>";
        },
        'type' => 'html',
    ],

    [
        'name' => 'releaseRequest.rr_build_version',
        'filter' => CHtml::activeTelField($model, 'build_version'),
    ],
    'obj_created',
    'migration_release_request_obj_id',


);
