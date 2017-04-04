<?php
/** @var $model MaintenanceToolRun */

use app\models\MaintenanceToolRun;

$this->title = "Выполнение процесса " . $model->mtrMaintenanceTool->mt_name;

$this->params['menu'] = array(
    array('label' => 'List MaintenanceToolRun', 'url' => array('index')),
    array('label' => 'Create MaintenanceToolRun', 'url' => array('create')),
    array('label' => 'Update MaintenanceToolRun', 'url' => array('update', 'id' => $model->obj_id)),
    array('label' => 'Manage MaintenanceToolRun', 'url' => array('admin')),
);
?>

<h1>View MaintenanceToolRun #<?php echo $model->obj_id; ?></h1>

<?php
echo yii\widgets\DetailView::widget([
    'model' => $model,
    'attributes' => [
        'obj_id',
        'obj_created',
        'obj_modified',
        'obj_status_did',
        'mtr_maintenance_tool_obj_id',
        'mtr_runner_user',
        'mtr_pid',
        'mtr_status',
        [
            'attribute' => 'progress',
            'value' => function ($model) {
                if (!$model->isInProgress()) {
                    if ($model->mtr_status == MaintenanceToolRun::STATUS_DONE) {
                        return '100%';
                    }

                    return null;
                }

                list($percent, $key) = $model->getProgressPercentAndKey();

                return '<div class="progress progress-' . $model->obj_id . '" style="margin: 0; width: 250px;">
                            <div class="progress-bar" style="width: ' . (int) $percent . '%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                                <b>' . sprintf("%.2f", $percent) . '%</b>: ' . $key . '
                            </div>
                        </div>';
            },
            'format' => 'html',
        ],
        [
            'attribute' => 'mtr_log',
            'value' => function ($model) {
                return "<pre class='pre'>$model->mtr_log</pre>";
            },
            'format' => 'html',
        ],
    ],
]);
?>

<script type="text/javascript">
    document.onload.push(function () {
        webSocketSubscribe('maintenanceToolProgressbarChanged', function (event) {
            console.log(event);
            $('.progress-' + event.id + ' .bar').css({width: event.percent + '%'});
            var html = '<b>' + (event.percent.toFixed(2).toString()) + '%:</b> ' + (event.key);
            $('.progress-' + event.id + ' .bar').html(html);
            $('.progress-action-' + event.id).html(event.key);
        });

        webSocketSubscribe('maintenance_tool_log_<?=$model->obj_id?>', function (event) {
            $('.pre').append('<span>' + event.text + '</span>');
            var span = $('.pre span:last');
            span.css({fontWeight: 'bold'});
            setTimeout(function () {
                span.css({fontWeight: 'normal'});
            }, 250);
            $('body').scrollTop($('body').height())
        });
        $('body').scrollTop($('body').height());
    })
</script>
