<?php
/* @var $this BuildController */
/* @var $model Build */

$this->breadcrumbs=array(
	'Builds'=>array('index'),
	$model->obj_id,
);

$this->menu=array(
	array('label'=>'Update Build', 'url'=>array('update', 'id'=>$model->obj_id)),
	array('label'=>'Delete Build', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->obj_id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Build', 'url'=>array('admin')),
);
?>

<h1>View Build #<?php echo $model->obj_id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'obj_id',
		'obj_created',
		'obj_modified',
		'obj_status_did',
		'build_release_request_obj_id',
		'build_worker_obj_id',
		'build_project_obj_id',
		'build_status',
		'build_version',
        [
            'name' => 'build_time_log',
            'type'=>'html',
            'value' => function(Build $build){
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
                        <?
                        $max = 0;
                        $prev = 0;
                        foreach ($data as $val) {
                            $max = max($max, $val-$prev);
                            $prev = $val;
                        }
                        $prevName = 'init';
                        $prev = 0;
                        ?>
                        <?foreach ($data as $name => $time) {?>
                            <tr>
                                <td><?=$prevName?></td>
                                <td>
                                    <div class="progress" style="margin: 0">
                                        <div class="bar" role="progressbar" style="width: <?=100*($time - $prev)/$max?>%; color: black">
                                            <?=sprintf("%.2f", $time - $prev)?>
                                        </div>
                                    </div>

                                </td>
                                <td><?=sprintf("%.2f", $time)?></td>
                            </tr>
                            <?$prev = $time;?>
                            <?$prevName = $name;?>
                        <?}?>
                    </table>
                <?
                return ob_get_clean();
            }
        ],
		array(
            'name' => 'build_attach',
            'value' => function($e){
                return $this->cliColorsToHtml($e->build_attach);
            },
            'type' => 'html',
        ),
	),
)); ?>
