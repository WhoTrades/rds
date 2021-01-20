<?php
/**
 *
 */

use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Project;
use whotrades\rds\models\Build;
use \whotrades\rds\helpers\Html;
use yii\bootstrap\Alert;
use yii\bootstrap\Modal;
use yii\helpers\Url;

return array(
    [
        'attribute' => 'obj_created',
        'value' => function (ReleaseRequest $releaseRequest) {
            $dateTime = new DateTime($releaseRequest->obj_created);
            if (!$dateTime) {
                return '';
            }
            return Html::tag("div", $dateTime->format('c'), ['data-datetime' => $dateTime->format('c')]);
        },
        'format' => 'raw',
    ],
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
            $releaseRequestCommentGenerator = Yii::$app->params['releaseRequestCommentGenerator'] ?? '';
            if (empty($releaseRequestCommentGenerator) || !is_callable($releaseRequestCommentGenerator)) {
                return strip_tags($releaseRequest->rr_comment) . "<br />";
            }

            return call_user_func($releaseRequestCommentGenerator, $releaseRequest);
        },
        'format' => 'raw',
    ],
    array(
        'value' => function (ReleaseRequest $releaseRequest) {
            $map = array(
                ReleaseRequest::STATUS_NEW          => array('time', Yii::t('rds', 'rr_status_waiting_for_build'), 'black'),
                ReleaseRequest::STATUS_FAILED       => array('remove', Yii::t('rds', 'rr_status_failed'), 'red'),
                ReleaseRequest::STATUS_BUILDING     => array('ok', Yii::t('rds', 'rr_status_building'), 'orange'),
                ReleaseRequest::STATUS_BUILT        => array('ok', Yii::t('rds', 'rr_status_built'), 'black'),
                ReleaseRequest::STATUS_INSTALLING   => array('ok', Yii::t('rds', 'rr_status_installing'), 'orange'),
                ReleaseRequest::STATUS_INSTALLED    => array('ok', Yii::t('rds', 'rr_status_installed'), 'black'),
                ReleaseRequest::STATUS_USING        => array('refresh', Yii::t('rds', 'rr_status_using'), 'orange'),
                ReleaseRequest::STATUS_USED         => array('ok', Yii::t('rds', 'rr_status_used'), '#32cd32'),
                ReleaseRequest::STATUS_OLD          => array('time', Yii::t('rds', 'rr_status_old'), 'grey'),
                ReleaseRequest::STATUS_CANCELLING   => array('refresh', Yii::t('rds', 'rr_status_cancelling'), 'orange'),
                ReleaseRequest::STATUS_CANCELLED    => array('ok', Yii::t('rds', 'rr_status_cancelled'), 'red'),
            );
            list($icon, $text, $color) = $map[$releaseRequest->rr_status];
            $result = ["<span title='{$text}' style='color: " . $color . "'>" . yii\bootstrap\BaseHtml::icon($icon) . " {$releaseRequest->rr_status}</span><br />"];

            foreach ($releaseRequest->builds as $val) {
                /** @var $val Build */
                $map = array(
                    Build::STATUS_NEW => array('time', Yii::t('rds', 'build_status_waiting'), 'black'),
                    Build::STATUS_FAILED => array('exclamation-sign', Yii::t('rds', 'build_status_failed'), 'red'),
                    Build::STATUS_BUILDING => array('refresh', Yii::t('rds', 'build_status_building'), 'orange'),
                    Build::STATUS_BUILT => array('upload', Yii::t('rds', 'build_status_built'), 'orange'),
                    Build::STATUS_INSTALLING => array('ok', Yii::t('rds', 'build_status_installing'), 'black'),
                    Build::STATUS_INSTALLED => array('ok', Yii::t('rds', 'build_status_installed'), 'black'),
                    Build::STATUS_USED => array('ok', Yii::t('rds', 'build_status_used'), '#32cd32'),
                    Build::STATUS_CANCELLED => array('ban-circle', Yii::t('rds', 'build_status_cancelled'), 'red'),
                    Build::STATUS_PREPROD_USING => array('refresh', Yii::t('rds', 'build_status_preprod_using'), 'orange'),
                    Build::STATUS_PREPROD_MIGRATIONS => array('refresh', Yii::t('rds', 'build_status_preprod_migrations'), 'orange'),
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
            ReleaseRequest::STATUS_BUILDING => ReleaseRequest::STATUS_BUILDING,
            ReleaseRequest::STATUS_BUILT => ReleaseRequest::STATUS_BUILT,
            ReleaseRequest::STATUS_INSTALLING => ReleaseRequest::STATUS_INSTALLING,
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
        'attribute' => 'builds.worker.worker_name',
        'value' => function (ReleaseRequest $releaseRequest) {
            $buildList = $releaseRequest->builds;
            
            if (empty($buildList[0]) || empty($worker = $buildList[0]->worker)) {
                return '';
            }

            $workerUrlGenerator = Yii::$app->params['workerUrlGenerator'] ?? '';
            if (empty($workerUrlGenerator) || !is_callable($workerUrlGenerator)) {
                return $worker->worker_name;
            }

            return Html::aTargetBlank(call_user_func($workerUrlGenerator, $worker), $worker->worker_name);
        },
        'format' => 'raw',
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
            $buildVersionMetricsGenerator = Yii::$app->params['buildVersionMetricsGenerator'] ?? null;
            if ($buildVersionMetricsGenerator && is_callable($buildVersionMetricsGenerator)) {
                return call_user_func($buildVersionMetricsGenerator, $r);
            }

            return $r->rr_build_version;
        },
        'format' => 'raw',
    ),
    array(
        'value' => function (ReleaseRequest $releaseRequest) {
            // TODO: refactor this mess
            if ($releaseRequest->isDeleted()) {
                return Yii::t('rds', 'release_deleted');
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
                        "'>" . Yii::t('rds', 'cron_changed') . "<br />$diffStat</a><br />";
                }
            }

            if ($releaseRequest->showInstallationErrors()) {
                Modal::begin(
                    [
                        'id' => 'release-request-install-error-' . $releaseRequest->obj_id,
                        'header' => Yii::t('rds/errors', 'deploy_error'),
                        'footer' => Html::button('Close', array('data-dismiss' => 'modal', 'class' => 'btn btn-default')),
                    ]
                );
                echo "<pre>$releaseRequest->rr_last_error_text</pre>";
                Modal::end();

                $result .= Html::a(Yii::t('rds/errors', 'deploy_error'), '#', [
                        'style' => 'info',
                        'data' => ['toggle' => 'modal', 'target' => '#release-request-install-error-' . $releaseRequest->obj_id, 'onclick' => "return false;"],
                    ]) . "<br />";
            }

            if ($releaseRequest->showActivationErrors()) {
                Modal::begin(
                    [
                        'id' => 'release-request-use-error-' . $releaseRequest->obj_id,
                        'header' => Yii::t('rds/errors', 'activation_error'),
                        'footer' => Html::button('Close', array('data-dismiss' => 'modal')),
                    ]
                );
                echo "<pre>$releaseRequest->rr_last_error_text</pre>";
                Modal::end();

                $result .= Html::a(Yii::t('rds/errors', 'activation_error'), '#', [
                        'style' => 'info',
                        'data' => ['toggle' => 'modal', 'target' => '#release-request-use-error-' . $releaseRequest->obj_id, 'onclick' => "return false;"],
                    ]) . "<br />";
            }

            $result .= "<div class=\"btn-group\">";
            if ($releaseRequest->canBeRecreated()) {
                $result .= "<a href='" . yii\helpers\Url::to(['/site/recreate-release', 'id' => $releaseRequest->obj_id]) .
                    "' class='ajax-url btn btn-default'>" . \yii\bootstrap\BaseHtml::icon('repeat') . ' ' . Yii::t('rds', 'btn_rebuild') . "</a>";

                if ($releaseRequest->rr_status === ReleaseRequest::STATUS_FAILED) {
                    return $result."</div>";
                }
            }

            if ($releaseRequest->shouldBeInstalled()) {
                $result .= "<a href='" . yii\helpers\Url::to(['/site/install-release', 'id' => $releaseRequest->obj_id]) .
                    "' --data-id='$releaseRequest->obj_id' class='install-button btn btn-primary'>" . Yii::t('rds', 'btn_deploy') . "</a>";

                return $result."</div>";
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
                    $result .= Html::a('Retry', ['/use/migrate', 'id' => $releaseRequest->obj_id], ['class' => 'ajax-url btn btn-primary']);
                    //$result .= "<br />";

                    return $result;
                } else {
                    $result .=
                        "<a href='" . yii\helpers\Url::to(['/use/migrate', 'id' => $releaseRequest->obj_id]) .
                        "' class='ajax-url btn btn-primary'>" . Yii::t('rds', 'btn_run_pre_migrations') . "</a><br />" .
                        "<a href='#' onclick=\"$('#migrations-{$releaseRequest->obj_id}').toggle('fast'); return false;\">" . Yii::t('rds', 'btn_view_pre_migrations') . "</a>
                                <div id='migrations-{$releaseRequest->obj_id}' style='display: none'>";
                    // ag: @see whotrades\rds\models\MigrationBase::getNameForUrl()
                    $getNameForUrl = function ($migrationName) {
                        return str_replace('\\', '/', $migrationName);
                    };
                    foreach (json_decode($releaseRequest->rr_new_migrations) as $migration) {
                        $result .= "<a href=" . $releaseRequest->project->getMigrationUrl($getNameForUrl($migration), \whotrades\rds\models\Migration::TYPE_PRE) . ">";
                        $result .= "$migration";
                        $result .= "</a>";
                    }
                    $result .= "</div>";

                    return $result."</div>";
                }
            }

            if ($releaseRequest->canBeUsed()) {
                if ($releaseRequest->isChild()) {
                    $result .= 'It is a child';

                    return $result;
                }

                $result .= "<a href='" . yii\helpers\Url::to(['/use/create', 'id' => $releaseRequest->obj_id]) .
                    "' --data-id='$releaseRequest->obj_id' class='use-button btn btn-primary'>" . Yii::t('rds', 'btn_activate_release') . "</a>";

                return $result."</div>";
            }

            if ($releaseRequest->canBeReverted()) {
                if ($releaseRequest->isChild()) {
                    $result .= "Prev version is {$releaseRequest->rr_old_version}";

                    return $result;
                }

                $result .= "<a href='" . yii\helpers\Url::to(['/use/revert', 'id' => $releaseRequest->obj_id]) .
                    "' --data-id='$releaseRequest->obj_id' class='use-button btn btn-warning'>" . Yii::t('rds', 'btn_revert_release', ['version' => $releaseRequest->rr_old_version]) . "</a>";

                return $result."</div>";
            }

            if (!$releaseRequest->canBeUsedChildren()) {
                $result .= 'Waiting for children...';

                return $result."</div>";
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
