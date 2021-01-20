<?php
/** @var $model whotrades\rds\models\Build */

use whotrades\rds\models\ReleaseRequest;

?>
<h1><?=Yii::t('rds', 'head_build_with_params', [$model->obj_id, $model->project->project_name . '-' . $model->releaseRequest->rr_build_version])?></h1>

<?php
echo yii\widgets\DetailView::widget([
    'model' => $model,
    'attributes' => [
        'obj_id',
        'worker.worker_name',
        'project.project_name',
        'build_status',
        'build_version',
        [
            'attribute' => 'build_time_log',
            'format' => 'raw',
            'value' => function (\whotrades\rds\models\Build $model) {
                $timeLogRaw   = json_decode($model->build_time_log, true);
                if (empty($timeLogRaw)) {
                    return '';
                }

                // ag: Backward compatibility with old build_time_log #WTA-1754
                if (reset($timeLogRaw) < strtotime($model->releaseRequest->obj_created)) {
                    $prevAction = 'init';
                    $prevTime = 0;
                    foreach ($timeLogRaw as $action => $time) {
                        $timeLogPrepared[$prevAction] = $time - $prevTime;
                        $prevAction = $action;
                        $prevTime = $time;
                    }
                    $timeLogPrepared[$prevAction . ' + queueing + install'] = strtotime($model->releaseRequest->rr_built_time) - strtotime($model->releaseRequest->obj_created) - $prevTime;
                } else {
                    $buildFinishTime = 0;
                    if (isset($timeLogRaw[ReleaseRequest::BUILD_LOG_BUILD_SUCCESS])) {
                        $buildFinishTime = $timeLogRaw[ReleaseRequest::BUILD_LOG_BUILD_SUCCESS];
                    } elseif (isset($buildTimeLog[ReleaseRequest::BUILD_LOG_BUILD_ERROR])) {
                        $buildFinishTime = $timeLogRaw[ReleaseRequest::BUILD_LOG_BUILD_ERROR];
                    }

                    $installFinishTime = $model->releaseRequest->rr_built_time ? strtotime($model->releaseRequest->rr_built_time) : null;

                    $stopProcessLogTime = $buildFinishTime ?: $installFinishTime;

                    $timeLogPrepared = [];
                    $prevTime = strtotime($model->releaseRequest->obj_created);
                    $prevAction = 'pre build queueing';
                    foreach ($timeLogRaw as $action => $time) {
                        if ($stopProcessLogTime && $stopProcessLogTime <= $time) {
                            break;
                        }

                        $timeLogPrepared[$prevAction] = $time - $prevTime;
                        $prevAction = $action;
                        $prevTime = $time;
                    }

                    if ($buildFinishTime) {
                        if ($installFinishTime) {
                            $timeLogPrepared[$prevAction] = $buildFinishTime - $prevTime;
                        }
                    } else {
                        if ($installFinishTime) {
                            $timeLogPrepared[$prevAction . ' + install'] = $installFinishTime - $prevTime;
                        }
                    }

                    if (isset($timeLogRaw[ReleaseRequest::BUILD_LOG_INSTALL_START])) {
                        if ($buildFinishTime) {
                            $timeLogPrepared['pre install queueing'] = $timeLogRaw[ReleaseRequest::BUILD_LOG_INSTALL_START] - $buildFinishTime;
                        }

                        if (isset($timeLogRaw[ReleaseRequest::BUILD_LOG_INSTALL_SUCCESS])) {
                            $timeLogPrepared['install'] = $timeLogRaw[ReleaseRequest::BUILD_LOG_INSTALL_SUCCESS] - $timeLogRaw[ReleaseRequest::BUILD_LOG_INSTALL_START];
                        } elseif (isset($buildTimeLog[ReleaseRequest::BUILD_LOG_INSTALL_ERROR])) {
                            $timeLogPrepared['install'] = $timeLogRaw[ReleaseRequest::BUILD_LOG_INSTALL_ERROR] - $timeLogRaw[ReleaseRequest::BUILD_LOG_INSTALL_START];
                        }
                    }

                    if (isset($timeLogRaw[ReleaseRequest::BUILD_LOG_USING_START]) && isset($timeLogRaw[ReleaseRequest::BUILD_LOG_USING_SUCCESS])) {
                        $timeLogPrepared['activating'] = round($timeLogRaw[ReleaseRequest::BUILD_LOG_USING_SUCCESS] - $timeLogRaw[ReleaseRequest::BUILD_LOG_USING_START]);
                    }
                }

                $maxTime = max($timeLogPrepared);


                $content = '';
                $content .= '<table class="table table-condensed table-responsive">';
                $content .=     '<thead>';
                $content .=         '<tr style="font-weight: bold">';
                $content .=             '<td>'.Yii::t('rds', 'action').'</td><td>'. Yii::t('rds', 'spent_time') .'</td><td>'.Yii::t('rds', 'time_scale').'</td>';
                $content .=         '</tr>';
                $content .=     '</thead>';
                $progressTime = 0;
                foreach ($timeLogPrepared as $action => $time) {
                    $progressTime += $time;
                    $percent = 100 * $time / $maxTime;
                    $content .= '<tr>';
                    $content .=     '<td>' . $action . '</td>';
                    $content .=     '<td>' . sprintf("%.2f", $progressTime) . '</td>';
                    $content .=     '<td>';
                    $content .=         '<div class="progress" style="margin: 0">';
                    $content .=             '<div class="progress-bar" style="color: black; width:' . $percent . '%">' . sprintf("%.2f", $time) . '</div>';
                    $content .=         '</div>';
                    $content .=     '</td>';
                    $content .= '</tr>';
                }
                $content .= '</table>';

                return $content;
            },
        ],
        [
            'attribute' => 'build_attach',
            'format' => 'html',
            'value' => function ($model) {
                return $this->context->cliColorsToHtml($model->build_attach);
            },
        ],
    ],
]);
