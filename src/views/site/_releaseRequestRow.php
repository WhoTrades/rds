<?php
/**
 *
 */

use app\models\ReleaseRequest;
use app\models\Project;
use app\models\Build;
use yii\bootstrap\Html;
use yii\bootstrap\Alert;

return array(
    'obj_id',
    'obj_created',
    'rr_user',
    [
        'attribute' => 'rr_comment',
        'value' => function (ReleaseRequest $releaseRequest) {
            $result = strip_tags($releaseRequest->rr_comment) . "<br />";

            if ($releaseRequest->isInstalledStatus()) {
                $result .= "<a href='" . yii\helpers\Url::to('/Wtflow/jira/gotoJiraTicketsByReleaseRequest', ['id' => $releaseRequest->obj_id]) .
                    "' target='_blank'>Тикеты</a><br />";
            }
            $result .= "<a href='/site/commits/$releaseRequest->obj_id' onclick=\"popup('test', this.href, {id: {$releaseRequest->obj_id}}); return false;\">Комиты</button>";

            return $result;
        },
        'format' => 'raw',
    ],
    array(
        'value' => function (ReleaseRequest $releaseRequest) {
            $map = array(
                ReleaseRequest::STATUS_NEW          => array('time', 'Ожидает сборки', 'black'),
                ReleaseRequest::STATUS_FAILED       => array('remove', 'Не собралось', 'red'),
                ReleaseRequest::STATUS_INSTALLED    => array('ok', 'Установлено', 'black'),
                ReleaseRequest::STATUS_USING        => array('refresh', 'Активируем', 'orange'),
                ReleaseRequest::STATUS_CODES        => array('time', 'Ждем ввода кодов', 'orange'),
                ReleaseRequest::STATUS_USED         => array('ok', 'Активная версия', '#32cd32'),
                ReleaseRequest::STATUS_OLD          => array('time', 'Старая версия', 'grey'),
                ReleaseRequest::STATUS_CANCELLING   => array('refresh', 'Отменяем...', 'orange'),
                ReleaseRequest::STATUS_CANCELLED    => array('ok', 'Отменено', 'red'),
            );
            list($icon, $text, $color) = $map[$releaseRequest->rr_status];
            $result = ["<span title='{$text}' style='color: " . $color . "'>" . yii\bootstrap\BaseHtml::icon($icon) . " {$releaseRequest->rr_status}</span><hr />"];

            foreach ($releaseRequest->builds as $val) {
                /** @var $val Build */
                $map = array(
                    Build::STATUS_FAILED => array('exclamation-sign', 'Не собралось', 'red'),
                    Build::STATUS_BUILDING => array('refresh', 'Собирается', 'orange'),
                    Build::STATUS_NEW => array('time', 'Ожидает сборки', 'black'),
                    Build::STATUS_BUILT => array('upload', 'Раскладывается по серверам', 'orange'),
                    Build::STATUS_INSTALLED => array('ok', 'Скопировано на сервер', 'black'),
                    Build::STATUS_USED => array('ok', 'Установлено', '#32cd32'),
                    Build::STATUS_CANCELLED => array('ban-circle', 'Отменено', 'red'),
                    Build::STATUS_PREPROD_USING => array('refresh', 'Устанавливаем на preprod', 'orange'),
                    Build::STATUS_PREPROD_MIGRATIONS => array('refresh', 'Устанавливаем на preprod', 'orange'),
                );
                list($icon, $text, $color) = $map[$val->build_status];

                $result[] =  implode("", [
                    "<a href='" . yii\helpers\Url::to('build/view', array('id' => $val->obj_id)),
                    "' title='{$text}' style='color: $color'>",
                    yii\bootstrap\BaseHtml::icon($icon),
                    " {$val->build_status} {$val->project->project_name} {$val->build_version}</a>",
                ]);

                if ($val->build_status == Build::STATUS_BUILDING) {
                    $info = $val->getProgressbarInfo();
                    if ($info) {
                        list($percent, $currentKey) = $info;
                        $result[] = '
                            <div class="progress progress-' . $val->obj_id . '" style="margin: 0; width: 250px;">
                            <div class="progress-bar" style="width: ' . $percent . '%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                                <b>' . sprintf("%.2f", $percent) . '%:</b> ' . $currentKey . '
                            </div>
                            </div>';
                    }
                }

                if ($text = $val->determineHumanReadableError()) {
                    $result[] = Alert::widget(['options' => ['class' => 'alert-warning'], 'body' => $text]);
                }
            }

            return implode("<br />", $result);
        },
        'attribute' => 'rr_status',
        'filter' => array(
            ReleaseRequest::STATUS_NEW => ReleaseRequest::STATUS_NEW,
            ReleaseRequest::STATUS_FAILED => ReleaseRequest::STATUS_FAILED,
            ReleaseRequest::STATUS_INSTALLED => ReleaseRequest::STATUS_INSTALLED,
            ReleaseRequest::STATUS_USING => ReleaseRequest::STATUS_USING,
            ReleaseRequest::STATUS_CODES => ReleaseRequest::STATUS_CODES,
            ReleaseRequest::STATUS_USED => ReleaseRequest::STATUS_USED,
            ReleaseRequest::STATUS_OLD => ReleaseRequest::STATUS_OLD,
            ReleaseRequest::STATUS_CANCELLING => ReleaseRequest::STATUS_CANCELLING,
            ReleaseRequest::STATUS_CANCELLED => ReleaseRequest::STATUS_CANCELLED,
        ),
        'format' => 'html',
    ),
    array(
        'attribute' => 'rr_project_obj_id',
        'value' => function ($r) {
            return $r->builds[0]->project->project_name;
        },
        'filter' => Project::forList(),
    ),
    array(
        'attribute' => 'rr_build_version',
        'value' => function (ReleaseRequest $r) {
            if ($r->rr_built_time) {
                $time = strtotime($r->rr_built_time) - strtotime($r->obj_created);

                return $r->rr_build_version . "<br /><br />Собрано за <b>$time</b> сек.";
            } else {
                return $r->rr_build_version;
            }
        },
        'format' => 'html',
    ),
    array(
        'value' => function (ReleaseRequest $releaseRequest) {
            $result = "";
            if ($releaseRequest->canBeUsed()) {
                /** @var $currentUsed ReleaseRequest */
                $currentUsed = ReleaseRequest::find()->where([
                    'rr_status' => ReleaseRequest::getUsedStatuses(),
                    'rr_project_obj_id' => $releaseRequest->rr_project_obj_id,
                ])->one();

                if ($currentUsed && $currentUsed->getCronConfigCleaned() != $releaseRequest->getCronConfigCleaned()) {
                    $diffStat = \Yii::$app->diffStat->getDiffStat($currentUsed->getCronConfigCleaned(), $releaseRequest->getCronConfigCleaned());
                    $diffStat = preg_replace('~\++~', '<span style="color: #32cd32">$0</span>', $diffStat);
                    $diffStat = preg_replace('~\-+~', '<span style="color: red">$0</span>', $diffStat);
                    $result .= "<a href='" . yii\helpers\Url::to(['/diff/index/', 'id1' => $releaseRequest->obj_id, 'id2' => $currentUsed->obj_id]) .
                        "'>CRON изменен<br />$diffStat</a><br />";
                }

                if ($releaseRequest->hardMigrations) {
                    $result .= "<a href='" . yii\helpers\Url::to('hardMigration/index', ['HardMigration[migration_release_request_obj_id]' => $releaseRequest->obj_id]) .
                        "'>Покажать тяжелые миграции (" . count($releaseRequest->hardMigrations) . ")</a><br />";
                }

                if ($releaseRequest->rr_new_migration_count) {
                    if ($releaseRequest->rr_migration_status == ReleaseRequest::MIGRATION_STATUS_UPDATING) {
                        return "updating migrations";
                    } elseif ($releaseRequest->rr_migration_status == ReleaseRequest::MIGRATION_STATUS_FAILED) {
                        $result .= "updating migrations failed<br />";
                        $widget = \Yii::$app->getWidgetFactory()->createWidget(\Yii::$app, 'yiistrap.widgets.TbModal', array(
                            'id' => 'release-request-migration-error-' . $releaseRequest->obj_id,
                            'header' => 'Errors of migration applying',
                            'content' => "<pre>$releaseRequest->rr_migration_error</pre>",
                            'footer' => array(
                                Html::button('Close', array('data-dismiss' => 'modal')),
                            ),
                        ));
                        $widget->init();
                        $widget->run();

                        $result .= '<a href="" style="info" data-toggle="modal" data-target="#release-request-migration-error-' .
                            $releaseRequest->obj_id . '" onclick="return false;">view error</a> | ';
                        $result .= "<a href='" . yii\helpers\Url::to('/use/migrate', array('id' => $releaseRequest->obj_id, 'type' => 'pre')) .
                            "' class='ajax-url'>Retry</a><br />";

                        return $result;
                    } else {
                        $result .=
                            "<a href='" . yii\helpers\Url::to('/use/migrate', array('id' => $releaseRequest->obj_id, 'type' => 'pre')) .
                                "' class='ajax-url'>Запустить pre-миграции</a><br />" .
                                "<a href='#' onclick=\"$('#migrations-{$releaseRequest->obj_id}').toggle('fast'); return false;\">Показать pre миграции</a>
                                <div id='migrations-{$releaseRequest->obj_id}' style='display: none'>";
                        foreach (json_decode($releaseRequest->rr_new_migrations) as $migration) {
                            $result .= "<a href=" . $releaseRequest->project->getMigrationUrl($migration, 'pre') . ">";
                            $result .= "$migration";
                            $result .= "</a><br />";
                        }
                        $result .= "</div>";

                        return $result;
                    }
                } else {
                    return "<a href='" . yii\helpers\Url::to('/use/create', array('id' => $releaseRequest->obj_id)) .
                        "' --data-id='$releaseRequest->obj_id' class='use-button'>Активировать</a>";
                }
            } elseif ($releaseRequest->rr_status == ReleaseRequest::STATUS_CODES) {
                return "<a href='" . yii\helpers\Url::to('/use/index', array('id' => $releaseRequest->obj_id)) .
                    "' onclick='showForm($releaseRequest->obj_id); return false;'>Ввести sms код</a>";
            } elseif ($releaseRequest->rr_status == ReleaseRequest::STATUS_USED && $releaseRequest->rr_old_version) {
                if ($oldReleaseRequest = $releaseRequest->getOldReleaseRequest($releaseRequest->rr_project_obj_id, $releaseRequest->rr_old_version)) {
                    if ($oldReleaseRequest->canBeUsed()) {
                        return "<a href='" . yii\helpers\Url::to('/use/create', array('id' => $oldReleaseRequest->obj_id)) .
                            "' --data-id='$oldReleaseRequest->obj_id' class='use-button'>Откатить до $releaseRequest->rr_old_version</a>";
                    } elseif ($oldReleaseRequest->rr_status == ReleaseRequest::STATUS_CODES) {
                        return "<a href='" . yii\helpers\Url::to('/use/index', array('id' => $oldReleaseRequest->obj_id)) .
                            "' onclick='showForm($oldReleaseRequest->obj_id); return false;'>Sms код для отката до $releaseRequest->rr_old_version</a>";
                    }
                }
            }

            return "";
        },
        'format' => 'raw',
    ),
    array(
        'class' => yii\grid\ActionColumn::class,
        'template' => '{deleteReleaseRequest}',
        'buttons' => [
            'deleteReleaseRequest' => function ($url, $model, $key) {
                $options = array_merge([
                    'title' => Yii::t('yii', 'Delete'),
                    'aria-label' => Yii::t('yii', 'Delete'),
                    'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                    'data-method' => 'post',
                    'data-pjax' => '0',
                ]);

                return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, $options);
            },
        ],
    ),
);
