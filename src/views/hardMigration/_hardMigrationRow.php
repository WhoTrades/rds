<?php
/** @var $model HardMigration */
return array(
    [
        'name' => 'migration_environment',
        'filter' => ['main' => 'main', 'preprod' => 'preprod'],
    ],
    [
        'name' => 'migration_project_obj_id',
        'value' => function(HardMigration $migration){
            return $migration->project->project_name;
        },
        'filter' => Project::model()->forList(),
    ],
    [
        'name' => 'releaseRequest.rr_build_version',
        'filter' => CHtml::activeTelField($model, 'build_version'),
    ],
    'obj_created',
    'migration_release_request_obj_id',
    [
        'name' => 'migration_name',
        'value' => function(HardMigration $migration){
            return  "<a href='http://sources:8060/browse/migration-{$migration->project->project_name}/hard/$migration->migration_name.php?hb=true' target='_blank' title='Посмотреть исходный код миграции'>$migration->migration_name</a><br />";
        },
        'type' => 'html',
    ],
    [
        'name' => 'migration_ticket',
        'value' => function(HardMigration $migration){
            return "<a href='http://jira/browse/$migration->migration_ticket' target='_blank' title='Перейти в JIRA'>$migration->migration_ticket</a>";
        },
        'type' => 'html',
    ],
    [
        'name' => 'migration_status',
        'value' => function(HardMigration $migration){
            $map = array(
                HardMigration::MIGRATION_STATUS_NEW => array('time', 'Миграция ожидает запуска, ручного или автоматического', 'black'),
                HardMigration::MIGRATION_STATUS_IN_PROGRESS => array('refresh', 'Миграция выполняется', 'orange'),
                HardMigration::MIGRATION_STATUS_STARTED => array('refresh', 'Отправлен сигнал на запуск миграции, ожидаем пока скрипты отработают и миграция запустится', 'orange'),
                HardMigration::MIGRATION_STATUS_DONE=> array('ok', 'Миграция была успешно выполнена', '#32cd32'),
                HardMigration::MIGRATION_STATUS_FAILED => array('remove', 'Миграция была запущена и заверилась с ошибкой', 'red'),
                HardMigration::MIGRATION_STATUS_PAUSED=> array('time', 'Миграция была поставлена на паузу и может быть в любой момент снова запущена', 'blue'),
                HardMigration::MIGRATION_STATUS_STOPPED => array('remove', 'Миграция была остановлена отправкой сигнала KILL', 'red'),
            );

            list($icon, $text, $color) = $map[$migration->migration_status];
            echo "<span title='{$text}' style='color: $color; font-weight: bold'><span class='icon-$icon'></span>$migration->migration_status</span><br />";
            if ($migration->migration_log) {
                echo "<a href='".Yii::app()->createAbsoluteUrl('/hardMigration/log', ['id' => $migration->obj_id])."'>LOG</a>";
            }
        },
        'filter' => array_combine(HardMigration::getAllStatuses(), HardMigration::getAllStatuses()),
        'type' => 'html',
    ],
    'migration_retry_count',
    [
        'name' => 'migration_progress',
        'value' => function(HardMigration $migration){
            if (!in_array($migration->migration_status, [\HardMigration::MIGRATION_STATUS_IN_PROGRESS, \HardMigration::MIGRATION_STATUS_PAUSED])) {
                return false;
            }
            return '<div class="progress progress-'.$migration->obj_id.'" style="margin: 0; width: 250px;">
                        <div class="bar" role="progressbar"style="width: '.$migration->migration_progress.'%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                            <b>'.sprintf("%.2f", $migration->migration_progress).'%:</b> '.$migration->migration_progress_action.'
                        </div>
                    </div>';
        },
        'type' => 'html',
    ],
    [
        'name' => 'migration_progress_action',
        'value' => function(HardMigration $migration){
            echo "<div class='progress-action-$migration->obj_id'>$migration->migration_progress_action</div>";
        },
        'type' => 'html',
    ],

    array(
        'class'=>'CButtonColumn',
        'template' => '{warning} {start} {stop} {pause} {restart} {resume}',
        'buttons' => [
            'start' => [
                'visible' => '$data->canBeStarted()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/start",array("id"=>$data->primaryKey))',
                'label' => '<span class="icon-play" style="color: #32cd32"></span>',
                'options' => [
                    'title' => 'Запустить миграцию',
                ],
            ],
            'stop' => [
                'visible' => '$data->canBeStopped()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/stop",array("id"=>$data->primaryKey))',
                'label' => '<span class="icon-stop" style="color: #32cd32"></span>',
                'options' => [
                    'title' => 'Остановить миграцию',
                ],
            ],
            'pause' => [
                'visible' => '$data->canBePaused()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/pause",array("id"=>$data->primaryKey))',
                'label' => '<span class="icon-pause" style="color: #32cd32"></span>',
                'options' => [
                    'title' => 'Поставить на паузу',
                ],
            ],
            'resume' => [
                'visible' => '$data->canBeResumed()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/resume",array("id"=>$data->primaryKey))',
                'label' => '<span class="icon-play" style="color: #32cd32"></span>',
                'options' => [
                    'title' => 'Запустить миграцию',
                ],
            ],
            'restart' => [
                'visible' => '$data->canBeRestarted()',
                'url' => 'Yii::app()->controller->createAbsoluteUrl("/hardMigration/restart",array("id"=>$data->primaryKey))',
                'label' => '<span class="icon-play" style="color: #32cd32"></span>',
                'options' => [
                    'title' => 'Перезапустить миграцию',
                ],
            ],
            'warning' => [
                'visible' => '!$data->doesMigrationReleased()',
                'label' => '<span class="icon-lock" style="color: #32cd32; cursor: default"></span>',
                'options' => [
                    'title' => 'Проект ещё не выложен, миграции пока нет на серверах, потому её нельзя накатить',
                ],
            ],
        ],
    ),
);