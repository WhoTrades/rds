<?php
/** @var $this BuildController */
/** @var $model Build */
?>

    <h1>Сборка #<?php echo $model->obj_id; ?> (<?= $model->project->project_name ?>
        -<?= $model->releaseRequest->rr_build_version ?>)</h1>

<?php $this->widget('TbDetailView', array(
    'data' => $model,
    'attributes' => array(
        'obj_id',
        'worker.worker_name',
        'project.project_name',
        'build_status',
        'build_version',
        [
            'name' => 'build_time_log',
            'type' => 'html',
            'value' => function (Build $build) {
                $data = json_decode($build->build_time_log, true);
                ob_start();
                ?>
                <table>
                    <thead>
                    <tr style="font-weight: bold">
                        <td>Название действия</td>
                        <td>Затраченное время</td>
                        <td>Временная шкала</td>
                    </tr>
                    </thead>
                    <?php
                    $max = 0;
                    $prev = 0;
                    foreach ($data as $val) {
                        $max = max($max, $val - $prev);
                        $prev = $val;
                    }
                    $prevName = 'init';
                    $prev = 0;
                    foreach ($data as $name => $time) { ?>
                        <tr>
                            <td><?= $prevName ?></td>
                            <td>
                                <div class="progress" style="margin: 0">
                                    <div class="progress-bar"
                                         style="width: <?= 100 * ($time - $prev) / $max ?>%; color: black">
                                        <?= sprintf("%.2f", $time - $prev) ?>
                                    </div>
                                </div>

                            </td>
                            <td><?= sprintf("%.2f", $time) ?></td>
                        </tr>
                        <?php $prev = $time;
                        $prevName = $name;
                    } ?>
                </table>
                <?php

                return ob_get_clean();
            },
        ],
        array(
            'name' => 'build_attach',
            'value' => function ($e) {
                return $this->cliColorsToHtml($e->build_attach);
            },
            'type' => 'html',
        ),
    ),
));
