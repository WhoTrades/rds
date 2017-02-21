<?php
return [
    'mt_environment',
    'mt_name',
    'mt_command',
    [
        'value' => function(MaintenanceTool $tool){
            return
                $tool->lastRun
                    ? "<a href='".\Yii::$app->createAbsoluteUrl("/maintenanceToolRun/view", ['id' => $tool->lastRun->obj_id])."'>".$tool->lastRun->mtr_status."</a>"
                    : "<i>never runed</i>";
        },
        'type' => 'html',
    ],
    [
        'value' => function(MaintenanceTool $tool){
            $toolRun = $tool->lastRun;
            if (empty($toolRun) || !$toolRun->isInProgress()) {
                return;
            }

            list($percent, $key) = $toolRun->getProgressPercentAndKey();
            return '<div class="progress progress-'.$toolRun->obj_id.'" style="margin: 0; width: 250px;">
                            <div class="progress-bar" style="width: '.(int)$percent.'%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                                <b>'.sprintf("%.2f", $percent).'%</b>: '.$key.'
                            </div>
                        </div>';
        },
        'header' => 'Last run',
        'type' => 'html',
    ],
    [
        'header' => 'Run log',
        'value' => function(MaintenanceTool $tool){
            return '<a href="'.\Yii::$app->createAbsoluteUrl("/maintenanceToolRun/index", ['MaintenanceToolRun[mtr_maintenance_tool_obj_id]' => $tool->obj_id]).'">view all runs</a>';
        },
        'type' => 'html',
    ],
    [
        'class'=>'yiistrap.widgets.TbButtonColumn',
        'template' => '{start} {stop}',
        'buttons' => [
            'start' => [
                'visible' => '$data->canBeStarted()',
                'url' => '\Yii::$app->controller->createAbsoluteUrl("/maintenanceTool/start",array("id"=>$data->primaryKey))',
                'label' => yii\bootstrap\BaseHtml::icon('play', ['style' => 'color: #32cd32']),
                'options' => [
                    'title' => 'Запустить команду',
                ],
            ],
            'stop' => [
                'visible' => '$data->canBeKilled()',
                'url' => '\Yii::$app->controller->createAbsoluteUrl("/maintenanceTool/stop",array("id"=>$data->primaryKey))',
                'label' => yii\bootstrap\BaseHtml::icon('stop', ['style' => 'color: red']),
                'options' => [
                    'title' => 'Остановить команду',
                ],
            ],
        ],
    ],
];
