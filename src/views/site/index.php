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
        'project.project_name',
        array(
            'value' => function(ReleaseRequest $releaseRequest){
                $result = array();
                foreach ($releaseRequest->builds as $val) {
                    $map = array(
                        Build::STATUS_FAILED => array('remove', 'Не собралось', 'red'),
                        Build::STATUS_BUILDING => array('refresh', 'Собирается', 'orange'),
                        Build::STATUS_NEW => array('time', 'Ожидает сборки', 'black'),
                        Build::STATUS_BUILT => array('upload', 'Раскладывается по серверам', 'orange'),
                        Build::STATUS_INSTALLED => array('ok', 'Установлено', '#32cd32'),
                    );
                    list($icon, $text, $color) = $map[$val->build_status];
                    $result[] =  "<span title='{$text}' style='color: $color'><span class='icon-$icon'></span>{$val->worker->worker_name} - {$val->build_status} {$val->project->project_name} {$val->build_version}</span>";
                }

                return implode("<br />", $result);
            },
            'type' => 'html',
        ),
        array(
            'value' => function(ReleaseRequest $releaseRequest){
                return "<button>USE</button>";
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