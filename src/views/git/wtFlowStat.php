<?php
/* @var $this WtFlowStatController */
/* @var $model WtFlowStat */
?>
<h1>Статистика wtflow</h1>

<p>
    You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
    or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php $this->widget('TbGridView', array(
    'id'=>'wt-flow-stat-grid',
    'dataProvider'=>$model->search(),
    'filter'=>$model,
    'columns'=>array(
        'obj_created',
        [
            'name' => 'developer.finam_email',
            'filter' => TbHtml::dropDownList('WtFlowStat[developer_id]', $model->developer_id, Developer::model()->forList())
        ],
        'exit_code',
        'action',
        'ticket',
        'command',
        'time',
        [
            'name' => 'log',
            'value' => function(WtFlowStat $stat){
                $widget = Yii::app()->getWidgetFactory()->createWidget(Yii::app(), 'yiistrap.widgets.TbModal', array(
                    'id' => 'wtflow-stat-log-'.$stat->obj_id,
                    'header' => "Wtflow log of $stat->action ".$stat->developer->finam_email,
                    'content' => "<pre>$stat->log</pre>",
                    'footer' => array(
                        TbHtml::button('Close', array('data-dismiss' => 'modal')),
                    ),
                ));
                $widget->init();
                $widget->run();

                echo '<a href="" style="info" data-toggle="modal" data-target="#wtflow-stat-log-'.$stat->obj_id.'" onclick="return false;">Посмотреть</a>';
            }
        ],
    ),
)); ?>