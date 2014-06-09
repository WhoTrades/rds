<?php
/* @var $this SiteController */
$this->pageTitle=Yii::app()->name;
?>


<h1>Запреты релиза</h1>
<a href="<?=$this->createUrl('createReleaseReject')?>">Создать</a>
<?php $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'release-reject-grid',
    'dataProvider'=>$releaseRejectSearchModel->search(),
    'filter'=>$releaseRejectSearchModel,
    'columns'=>array(
        'obj_id',
        'obj_created',
        'rr_user',
        'rr_comment',
        'project.project_name',
        array(
            'class'=>'CButtonColumn',
            'template' => '{delete}',
            'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseReject",array("id"=>$data->primaryKey))',
        ),
))); ?>
<hr />
<h2>Запрос релиза</h2>
<a href="<?=$this->createUrl('createReleaseRequest')?>">Создать</a>
<?php $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'release-request-grid',
    'dataProvider'=>$releaseRequestSearchModel->search(),
    'filter'=>$releaseRequestSearchModel,
    'columns'=>array(
        'obj_id',
        'obj_created',
        'rr_user',
        'rr_comment',
        array(
            'value' => function(ReleaseRequest $releaseRequest){
                $map = array(
                    ReleaseRequest::STATUS_NEW => array('time', 'Ожидает сборки', 'black'),
                    ReleaseRequest::STATUS_FAILED => array('remove', 'Не собралось', 'red'),
                    ReleaseRequest::STATUS_INSTALLED => array('ok', 'Установлено', 'black'),
                    ReleaseRequest::STATUS_USING=> array('refresh', 'Активируем', 'orange'),
                    ReleaseRequest::STATUS_CODES=> array('time', 'Ждем ввода кодов', 'orange'),
                    ReleaseRequest::STATUS_USED=> array('ok', 'Активная версия', '#32cd32'),
                    ReleaseRequest::STATUS_USED_ATTEMPT=> array('time', 'Временная версия', 'blue'),
                    ReleaseRequest::STATUS_OLD=> array('time', 'Старая версия', 'grey'),
                    ReleaseRequest::STATUS_CANCELLING=> array('refresh', 'Отменяем...', 'orange'),
                    ReleaseRequest::STATUS_CANCELLED=> array('ok', 'Отменено', 'red'),
                );
                list($icon, $text, $color) = $map[$releaseRequest->rr_status];
                echo "<span title='{$text}' style='color: $color'><span class='icon-$icon'></span>{$releaseRequest->rr_status}</span><hr />";

                $result = array();
                foreach ($releaseRequest->builds as $val) {
                    $map = array(
                        Build::STATUS_FAILED => array('remove', 'Не собралось', 'red'),
                        Build::STATUS_BUILDING => array('refresh', 'Собирается', 'orange'),
                        Build::STATUS_NEW => array('time', 'Ожидает сборки', 'black'),
                        Build::STATUS_BUILT => array('upload', 'Раскладывается по серверам', 'orange'),
                        Build::STATUS_INSTALLED => array('ok', 'Скопировано на сервер', 'black'),
                        Build::STATUS_USED=> array('ok', 'Установлено', '#32cd32'),
                        ReleaseRequest::STATUS_CANCELLED=> array('remove', 'Отменено', 'red'),
                    );
                    list($icon, $text, $color) = $map[$val->build_status];
                    $result[] =  "<span title='{$text}' style='color: $color'><span class='icon-$icon'></span>{$val->worker->worker_name} - {$val->build_status} {$val->project->project_name} {$val->build_version}</span>";
                }

                return implode("<br />", $result);
            },
            'name' => 'rr_status',
            'filter' => array(
                ReleaseRequest::STATUS_NEW => ReleaseRequest::STATUS_NEW,
                ReleaseRequest::STATUS_FAILED => ReleaseRequest::STATUS_FAILED,
                ReleaseRequest::STATUS_INSTALLED => ReleaseRequest::STATUS_INSTALLED,
                ReleaseRequest::STATUS_USING => ReleaseRequest::STATUS_USING,
                ReleaseRequest::STATUS_CODES => ReleaseRequest::STATUS_CODES,
                ReleaseRequest::STATUS_USED => ReleaseRequest::STATUS_USED,
                ReleaseRequest::STATUS_USED_ATTEMPT => ReleaseRequest::STATUS_USED_ATTEMPT,
                ReleaseRequest::STATUS_OLD => ReleaseRequest::STATUS_OLD,
                ReleaseRequest::STATUS_CANCELLING => ReleaseRequest::STATUS_CANCELLING,
                ReleaseRequest::STATUS_CANCELLED => ReleaseRequest::STATUS_CANCELLED,
            ),
            'type' => 'html',
        ),
        array(
            'name' => 'rr_project_obj_id',
            'value' => function($r){return $r->project->project_name;},
            'filter' => \Project::model()->forList(),
        ),
        'rr_build_version',
        array(
            'value' => function(ReleaseRequest $releaseRequest){
                if ($releaseRequest->canBeUsed()) {
                    return "<a href='".$this->createUrl('/use/create', array('id' => $releaseRequest->obj_id))."'>USE</a>";
                } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_CODES) {
                    return "<a href='".$this->createUrl('/use/index', array('id' => $releaseRequest->obj_id))."'>Enter codes</a>";
                } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_USED_ATTEMPT) {
                    return "<a href='".$this->createUrl('/use/fixAttempt', array('id' => $releaseRequest->obj_id))."'>Make stable</a>";
                } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_USED && $releaseRequest->rr_old_version) {
                    $oldReleaseRequest = ReleaseRequest::model()->findByAttributes(array(
                        'rr_build_version' => $releaseRequest->rr_old_version,
                        'rr_project_obj_id' => $releaseRequest->rr_project_obj_id,
                    ));
                    if ($oldReleaseRequest && $oldReleaseRequest->canBeUsed()) {
                        return "<a href='".$this->createUrl('/use/create', array('id' => $oldReleaseRequest->obj_id))."'>Revert to $releaseRequest->rr_old_version</a>";
                    }
                }
            },
            'type' => 'raw'
        ),
        array(
            'class'=>'CButtonColumn',
            'template' => '{delete}',
            'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseRequest",array("id"=>$data->primaryKey))',
        ),
))); ?>

<small style="text-align: center; color: grey" class="reload-block">
    <span>Таблицы обновятся через <span class="seconds">0</span> сек.</span><br /><br />
</small>

<script>
    var timer = setInterval(function(){
        var val = parseInt($('.reload-block .seconds:first').html());
        val--;
        if (val == -1) {
            $.fn.yiiGridView.update("release-reject-grid");
            $.fn.yiiGridView.update("release-request-grid");
            $('.reload-block .seconds:first').html(10);
        } else {
            $('.reload-block .seconds:first').html(val);
        }
    }, 1000);
</script>
<style>
    .grid-view .filter-container input {
        max-width: 100px;
    }
</style>