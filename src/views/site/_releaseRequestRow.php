<?php
/**
 *
 */

use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Project;
use whotrades\rds\models\Build;
use yii\bootstrap\Html;
use yii\bootstrap\Alert;
use yii\bootstrap\Modal;
use yii\helpers\Url;

return array(
    'obj_created:datetime',
    [
        'value' => function (ReleaseRequest $releaseRequest) {
            $url = $releaseRequest->user->profile->getAvatarUrl(64);

            return Html::img($url, [
                'class' => 'img-rounded',
                'alt' => $releaseRequest->user->email,
                'title' => $releaseRequest->user->email,
            ]);
        },
        'format' => 'raw',
    ],
    [
        'attribute' => 'rr_comment',
        'value' => function (ReleaseRequest $releaseRequest) {
            $result = strip_tags($releaseRequest->rr_comment) . "<br />";

            if (Yii::$app->hasModule('Wtflow')) {
                if ($releaseRequest->isInstalledStatus()) {
                    $result .= 'Jira Tickets: ';
                    if (!$releaseRequest->isUsedStatus()) {
                        $result .= "<a href='" . yii\helpers\Url::to(['/Wtflow/jira/goto-jira-tickets-by-release-request', 'id' => $releaseRequest->obj_id, 'toType' => \app\modules\Wtflow\Jira\CommitHelper::RR_TYPE_USED]) .
                            "' target='_blank' onclick=\"window.open(this.href,'_blank');return false;\">To Used<img src='/images/open_new_window.png' alt='Open new window' style='margin-left:5px; margin-bottom:3px; width:13px;height:13px;'></a> ";
                    }
                    $result .= "<a href='" . yii\helpers\Url::to(['/Wtflow/jira/goto-jira-tickets-by-release-request', 'id' => $releaseRequest->obj_id, 'toType' => \app\modules\Wtflow\Jira\CommitHelper::RR_TYPE_OLD]) .
                        "' target='_blank' onclick=\"window.open(this.href,'_blank');return false;\">To Prev<img src='/images/open_new_window.png' alt='Open new window' style='margin-left:5px; margin-bottom:3px; width:13px;height:13px;'></a> ";
                    $result .= '<br />';
                }
                if (\app\modules\Wtflow\Jira\CommitHelper::getCommitsOfReleaseRequest($releaseRequest, \app\modules\Wtflow\Jira\CommitHelper::RR_TYPE_CURRENT)) {
                    $result .= "<a href='/Wtflow/git/commits/?id=$releaseRequest->obj_id' onclick=\"popup('Commits', this.href, {id: {$releaseRequest->obj_id}}); return false;\">Commits to prev</button>" .
                        "<img src='/images/open_popup_window.jpeg' alt='Open popup window' style='margin-left:5px; margin-bottom:3px; width:13px;height:13px;'>";
                } else {
                    $result .= 'No commits';
                }
            }

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
                ReleaseRequest::STATUS_USED         => array('ok', 'Активная версия', '#32cd32'),
                ReleaseRequest::STATUS_OLD          => array('time', 'Старая версия', 'grey'),
                ReleaseRequest::STATUS_CANCELLING   => array('refresh', 'Отменяем...', 'orange'),
                ReleaseRequest::STATUS_CANCELLED    => array('ok', 'Отменено', 'red'),
            );
            list($icon, $text, $color) = $map[$releaseRequest->rr_status];
            $result = ["<span title='{$text}' style='color: " . $color . "'>" . yii\bootstrap\BaseHtml::icon($icon) . " {$releaseRequest->rr_status}</span><br />"];

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
                    "<a href='" . yii\helpers\Url::to(['build/view', 'id' => $val->obj_id]),
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
                    $result[] = Alert::widget(['options' => ['class' => 'alert-warning'], 'body' => $text, 'closeButton' => false]);
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
            ReleaseRequest::STATUS_USED => ReleaseRequest::STATUS_USED,
            ReleaseRequest::STATUS_OLD => ReleaseRequest::STATUS_OLD,
            ReleaseRequest::STATUS_CANCELLING => ReleaseRequest::STATUS_CANCELLING,
            ReleaseRequest::STATUS_CANCELLED => ReleaseRequest::STATUS_CANCELLED,
        ),
        'format' => 'html',
    ),
    array(
        'attribute' => 'rr_project_obj_id',
        'value' => function (ReleaseRequest $r) {
            return $r->project->project_name;
        },
        'filter' => Project::forList(),
    ),
    array(
        'attribute' => 'rr_build_version',
        'value' => function (ReleaseRequest $r) {
            if ($r->rr_built_time) {
                $buildFinishTime = strtotime($r->rr_built_time);
                $buildTimeLog = json_decode((reset($r->builds))->build_time_log, true);
                $buildTime = round(end($buildTimeLog) - reset($buildTimeLog));
                $activationText = '';
                // ag: Backward compatibility with old build_time_log #WTA-1754
                if (reset($buildTimeLog) < strtotime($r->obj_created)) {
                    $timeFull = round($buildFinishTime - strtotime($r->obj_created));
                    $timeAdditional = $timeFull - $buildTime;
                    $additionalText = "Очередь+раскладка: <b>$timeAdditional</b> сек.";
                } else {
                    $timeQueueing = round(reset($buildTimeLog) - strtotime($r->obj_created));
                    $timeDeploying = 0;
                    foreach ($buildTimeLog as $action => $time) {
                        if ($buildFinishTime < $time) {
                            break;
                        }
                        $timeDeploying = round($buildFinishTime - $time);
                    }
                    if (isset($buildTimeLog[ReleaseRequest::BUILD_LOG_USING_START]) && isset($buildTimeLog[ReleaseRequest::BUILD_LOG_USING_SUCCESS])) {
                        $timeActivating = round($buildTimeLog[ReleaseRequest::BUILD_LOG_USING_SUCCESS] - $buildTimeLog[ReleaseRequest::BUILD_LOG_USING_START]);
                        $activationText = "Активация: <b>$timeActivating</b> сек.";
                    }
                    $additionalText = "Очередь: <b>$timeQueueing</b> сек. Раскладка: <b>$timeDeploying</b> сек.";
                }

                return $r->rr_build_version . "<br />Сборка: <b>$buildTime</b>  сек. " . ($activationText ?? '') . "<br />" . $additionalText;
            } else {
                return $r->rr_build_version;
            }
        },
        'format' => 'html',
    ),
    array(
        'value' => function (ReleaseRequest $releaseRequest) {
            if ($releaseRequest->isDeleted()) {
                return 'Сборка удалена';
            }

            $result = "";
            if ($releaseRequest->showCronDiff()) {
                /** @var $currentUsed ReleaseRequest */
                $currentUsed = ReleaseRequest::find()->where(
                    [
                        'rr_status' => ReleaseRequest::getUsedStatuses(),
                        'rr_project_obj_id' => $releaseRequest->rr_project_obj_id,
                    ]
                )->one();

                if ($currentUsed && $currentUsed->getCronConfigCleaned() != $releaseRequest->getCronConfigCleaned()) {
                    $diffStat = \Yii::$app->diffStat->getDiffStat($currentUsed->getCronConfigCleaned(), $releaseRequest->getCronConfigCleaned());
                    $diffStat = preg_replace('~\++~', '<span style="color: #32cd32">$0</span>', $diffStat);
                    $diffStat = preg_replace('~\-+~', '<span style="color: red">$0</span>', $diffStat);
                    $result .= "<a href='" . yii\helpers\Url::to(['/diff/index/', 'id1' => $releaseRequest->obj_id, 'id2' => $currentUsed->obj_id]) .
                        "'>CRON изменен<br />$diffStat</a><br />";
                }
            }

            if ($releaseRequest->showActivationErrors()) {
                Modal::begin(
                    [
                        'id' => 'release-request-use-error-' . $releaseRequest->obj_id,
                        'header' => 'Ошибка активации сборки',
                        'footer' => Html::button('Close', array('data-dismiss' => 'modal')),
                    ]
                );
                echo "<pre>$releaseRequest->rr_use_text</pre>";
                Modal::end();

                $result .= Html::a('Ошибка активации', '#', [
                        'style' => 'info',
                        'data' => ['toggle' => 'modal', 'target' => '#release-request-use-error-' . $releaseRequest->obj_id, 'onclick' => "return false;"],
                    ]) . "<br />";
            }

            if ($releaseRequest->shouldBeMigrated()) {
                if ($releaseRequest->rr_migration_status == ReleaseRequest::MIGRATION_STATUS_UP) {
                    return "Wrong migration status";
                } elseif ($releaseRequest->rr_migration_status == ReleaseRequest::MIGRATION_STATUS_UPDATING) {
                    return "Updating migrations...";
                } elseif ($releaseRequest->rr_migration_status == ReleaseRequest::MIGRATION_STATUS_FAILED) {
                    $result .= "updating migrations failed<br />";
                    Modal::begin(
                        [
                            'id' => 'release-request-migration-error-' . $releaseRequest->obj_id,
                            'header' => 'Errors of migration applying',
                            'footer' => Html::button('Close', array('data-dismiss' => 'modal')),
                        ]
                    );
                    echo "<pre>$releaseRequest->rr_migration_error</pre>";
                    Modal::end();

                    $result .= Html::a(
                        'view error', '#', [
                                        'style' => 'info',
                                        'data' => ['toggle' => 'modal', 'target' => '#release-request-migration-error-' . $releaseRequest->obj_id, 'onclick' => "return false;"],
                                    ]
                    );
                    $result .= ' | ';
                    $result .= Html::a('Retry', ['/use/migrate', 'id' => $releaseRequest->obj_id, 'type' => 'pre'], ['class' => 'ajax-url']);
                    $result .= "<br />";

                    return $result;
                } else {
                    $result .=
                        "<a href='" . yii\helpers\Url::to(['/use/migrate', 'id' => $releaseRequest->obj_id, 'type' => 'pre']) .
                        "' class='ajax-url'>Запустить pre-миграции</a><br />" .
                        "<a href='#' onclick=\"$('#migrations-{$releaseRequest->obj_id}').toggle('fast'); return false;\">Показать pre миграции</a>
                                <div id='migrations-{$releaseRequest->obj_id}' style='display: none'>";
                    foreach (json_decode($releaseRequest->rr_new_migrations) as $migration) {
                        $result .= "<a href=" . $releaseRequest->project->getMigrationUrl($migration, 'pre', $releaseRequest->rr_build_version) . ">";
                        $result .= "$migration";
                        $result .= "</a><br />";
                    }
                    $result .= "</div>";

                    return $result;
                }
            }

            if ($releaseRequest->canBeUsed()) {
                if ($releaseRequest->isChild()) {
                    $result .= 'It is a child';

                    return $result;
                }

                $result .= "<a href='" . yii\helpers\Url::to(['/use/create', 'id' => $releaseRequest->obj_id]) .
                    "' --data-id='$releaseRequest->obj_id' class='use-button'>Активировать</a>";

                return $result;
            }

            if ($releaseRequest->canBeReverted()) {
                if ($releaseRequest->isChild()) {
                    $result .= "Prev version is {$releaseRequest->rr_old_version}";

                    return $result;
                }

                $result .= "<a href='" . yii\helpers\Url::to(['/use/revert', 'id' => $releaseRequest->obj_id]) .
                    "' --data-id='$releaseRequest->obj_id' class='use-button'>Откатить до $releaseRequest->rr_old_version</a>";

                return $result;
            }

            if (!$releaseRequest->canBeUsedChildren()) {
                $result .= 'Waiting for children...';

                return $result;
            }

            return "";
        },
        'format' => 'raw',
    ),
    array(
        'class' => yii\grid\ActionColumn::class,
        'template' => '{deleteReleaseRequest}',
        'buttons' => [
            'deleteReleaseRequest' => function ($url, ReleaseRequest $model, $key) {
                if ($model->isChild() || $model->isDeleted()) {
                    return '';
                } else {
                    $options = array_merge(
                        [
                            'title' => Yii::t('yii', 'Delete'),
                            'aria-label' => Yii::t('yii', 'Delete'),
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                            'data-pjax' => '1',
                        ]
                    );

                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, $options);
                }
            },
            'urlCreator' => function (string $action, ReleaseRequest $model) {
                return Url::to(["/site/$action", 'id' => $model->obj_id, 'returnUrl' => Yii::$app->request->getUrl()]);
            },
        ],
    ),
);
