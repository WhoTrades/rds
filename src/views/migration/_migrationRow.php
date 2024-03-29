<?php

/** @var MigrationLogAggregatorUrlInterface $migrationLogAggregatorUrl */

use whotrades\rds\models\Migration;
use whotrades\rds\models\Project;
use yii\helpers\Url;
use whotrades\rds\helpers\Html;
use yii\bootstrap\Html as bootstrapHtml;
use whotrades\RdsSystem\Migration\LogAggregatorUrlInterface as MigrationLogAggregatorUrlInterface;

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

            if (Yii::$container->has('jiraUrl')) {
                $jiraTicketUrl = Yii::$container->get('jiraUrl')->getTicketUrl($migration->migration_ticket);

                return Html::aTargetBlank($jiraTicketUrl, $migration->migration_ticket);
            }

            return $migration->migration_ticket;
        },
        'format' => 'raw',
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
        'value' => function (Migration $migration) use ($migrationLogAggregatorUrl) {
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

            $lines[] = Html::aTargetBlank(
                $migrationLogAggregatorUrl->generateFiltered($migration->migration_name, $migration->getTypeName(), $migration->project->project_name),
                'View log'
            );

            return implode("<br />", $lines);
        },
        'format' => 'raw',
    ],
];
