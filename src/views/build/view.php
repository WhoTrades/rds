<?php
/** @var $model whotrades\rds\models\Build */
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
            'value' => function ($model) {
                $data   = json_decode($model->build_time_log, true);
                if (empty($data)) {
                    return '';
                }
                $max    = $prev = 0;

                foreach ($data as $val) {
                    $max = max($max, $val - $prev);
                    $prev = $val;
                }

                $prevName = 'init';
                $prev = 0;

                $content = '';
                $content .= '<table>';
                $content .=     '<thead>';
                $content .=         '<tr style="font-weight: bold">';
                $content .=             '<td>Название действия</td><td>Затраченное время</td><td>Временная шкала</td>';
                $content .=         '</tr>';
                $content .=     '</thead>';
                foreach ($data as $name => $time) {
                    $with = 100 * ($time - $prev) / $max;
                    $content .= '<tr>';
                    $content .=     '<td>' . $prevName . '</td>';
                    $content .=     '<td>';
                    $content .=         '<div class="progress" style="margin: 0">';
                    $content .=             '<div class="progress-bar" style="color: black; width:' . $with . '%">' . sprintf("%.2f", $time - $prev) . '</div>';
                    $content .=         '</div>';
                    $content .=     '</td>';
                    $content .=     '<td>' . sprintf("%.2f", $time) . '</td>';
                    $content .= '</tr>';

                    $prev = $time;
                    $prevName = $name;
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
