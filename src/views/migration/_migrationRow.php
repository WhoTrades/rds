<?php

use whotrades\rds\models\Migration;
use whotrades\rds\models\Project;
use yii\helpers\Url;
use whotrades\rds\helpers\Html;
use yii\bootstrap\Html as bootstrapHtml;

return [
    'obj_id',
    'obj_created:datetime',
    [
        'attribute' => 'migration_type',
        'value' => function (Migration $migration) {
            return $migration->getTypeName();
        },
        'filter' => Migration::getTypeIdToNameMap(),
    ],
    [
        'attribute' => 'migration_name',
        'value' => function (Migration $migration) {
            $migrationUrl = $migration->project->getMigrationUrl($migration->getNameForUrl(), $migration->getTypeName());

            return Html::aTargetBlank($migrationUrl, $migration->getNameForUrl());
        },
        'format' => 'raw',
    ],
    [
        'attribute' => 'migration_project_obj_id',
        'value' => function (Migration $migration) {
            return $migration->project->project_name;
        },
        'filter' => Project::forList(),
    ],
    'releaseRequest.rr_build_version',
    [
        'attribute' => 'migration_ticket',
        'value' => function (Migration $migration) {
            if (!$migration->migration_ticket) {
                return null;
            }

            if (Yii::$app->hasModule('Wtflow')) {
                $jiraTicketUrl = \app\modules\Wtflow\helpers\Jira::getJiraTicketUrl($migration->migration_ticket);
                return Html::aTargetBlank($jiraTicketUrl, $migration->migration_ticket);
            }

            return $migration->migration_ticket;
        },
        'format' => 'raw',
    ],
    [
        'header' => 'Auto Apply',
        'class' => 'yii\grid\ActionColumn',
        'template' => '{disable} {enable}',
        'visibleButtons' => [
            'disable' => function (Migration $migration) {
                return $migration->migration_auto_apply;
            },
            'enable' => function (Migration $migration) {
                return !$migration->migration_auto_apply;
            },
        ],
        'buttons' => [
            'disable' => function ($url, Migration $migration) {
                $url    = Url::to(['/migration/auto-apply-disable', 'migrationId' => $migration->obj_id]);

                return '<span class="glyphicon glyphicon-ok" style="color: green">' . Html::a(' Disable', $url);
            },
            'enable' => function ($url, Migration $migration) {
                $url    = Url::to(['/migration/auto-apply-enable', 'migrationId' => $migration->obj_id]);

                return '<span class="glyphicon glyphicon-remove" style="color: red">' . Html::a(' Enable', $url);
            },
        ],
    ],
    [
        'attribute' => 'obj_status_did',
        'value' => function(Migration $migration) {
            $statusLine = $migration->getStatusName();
            if ($migration->isFailed()) {
                $statusLine = "<span style='color:#ff0000'>" . bootstrapHtml::icon('warning-sign') . " {$statusLine}</span>";
            }

            return $statusLine;
        },
        'format' => 'html',
        'filter' => Migration::getStatusIdToNameMap(),
    ],
    [
        'header' => 'Action',
        'value' => function(Migration $migration) {
            $lines = [];

            if (($waitingDays = $migration->getWaitingDays()) > 0) {
                $lines[] = "Waiting {$waitingDays} days";
            }

            if ($migration->canBeApplied()) {
                $lines[] = Html::a('Apply', Url::to(['/migration/apply', 'migrationId' => $migration->obj_id]), ['class' => 'ajax-url']);
            }

            if ($migration->canBeRolledBack()) {
                $lines[] = Html::a('RollBack', Url::to(['/migration/roll-back', 'migrationId' => $migration->obj_id]), ['class' => 'ajax-url']);
            }

            if ($migration->migration_log) {
                $lines[] = Html::aTargetBlank(Url::to(['/migration/view-log', 'migrationId' => $migration->obj_id]), 'View log');
            }

            return implode("<br />", $lines);
        },
        'format' => 'raw',
    ],
];
