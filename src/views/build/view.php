<?php
/** @var $model whotrades\rds\models\Build */

use whotrades\rds\models\ReleaseRequest;

?>

    <h1>Сборка #<?php echo $model->obj_id; ?> (<?= $model->project->project_name ?>
        -<?= $model->releaseRequest->rr_build_version ?>)</h1>

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
                    $timeLogPrepared[$prevAction . ' + queueing + deploy'] = strtotime($model->releaseRequest->rr_built_time) - strtotime($model->releaseRequest->obj_created) - $prevTime;
                } else {
                    $buildFinishTime = $model->releaseRequest->rr_built_time ? strtotime($model->releaseRequest->rr_built_time) : null;
                    $timeLogPrepared = [];
                    $prevTime = strtotime($model->releaseRequest->obj_created);
                    $prevAction = 'queueing';
                    foreach ($timeLogRaw as $action => $time) {
                        if ($buildFinishTime && $buildFinishTime < $time) {
                            break;
                        }

                        $timeLogPrepared[$prevAction] = $time - $prevTime;
                        $prevAction = $action;
                        $prevTime = $time;
                    }

                    if ($buildFinishTime) {
                        $timeLogPrepared[$prevAction . ' + deploy'] = $buildFinishTime - $prevTime;
                    }

                    if (isset($timeLogRaw[ReleaseRequest::BUILD_LOG_USING_START]) && isset($timeLogRaw[ReleaseRequest::BUILD_LOG_USING_SUCCESS])) {
                        $timeLogPrepared['activating'] = round($timeLogRaw[ReleaseRequest::BUILD_LOG_USING_SUCCESS] - $timeLogRaw[ReleaseRequest::BUILD_LOG_USING_START]);
                    }
                }

                $maxTime = max($timeLogPrepared);


                $content = '';
                $content .= '<table>';
                $content .=     '<thead>';
                $content .=         '<tr style="font-weight: bold">';
                $content .=             '<td>Название действия</td><td>Затраченное время</td><td>Временная шкала</td>';
                $content .=         '</tr>';
                $content .=     '</thead>';
                $progressTime = 0;
                foreach ($timeLogPrepared as $action => $time) {
                    $progressTime += $time;
                    $percent = 100 * $time / $maxTime;
                    $content .= '<tr>';
                    $content .=     '<td>' . $action . '</td>';
                    $content .=     '<td>';
                    $content .=         '<div class="progress" style="margin: 0">';
                    $content .=             '<div class="progress-bar" style="color: black; width:' . $percent . '%">' . sprintf("%.2f", $time) . '</div>';
                    $content .=         '</div>';
                    $content .=     '</td>';
                    $content .=     '<td>' . sprintf("%.2f", $progressTime) . '</td>';
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
