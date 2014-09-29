<?php
/* @var $this SiteController */
/* @var $releaseRejectSearchModel ReleaseReject */
/* @var $releaseRequestSearchModel ReleaseRequest */
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
        'rr_release_version',
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
    'rowCssClassExpression' => function($index, $rr){
        return 'release-request-'.$rr->obj_id;
    },
    'columns'=>include('_releaseRequestRow.php'),
)); ?>

<small style="text-align: center; color: grey" class="reload-block">
    <span>Таблицы обновятся через <span class="seconds">0</span> сек.</span><br /><br />
</small>

<script>
    var timer = setInterval(function(){
        var element = $('.reload-block .seconds:first');
        var val = parseInt(element.html());
        val--;
        if (val == -1) {
            $.fn.yiiGridView.update("release-reject-grid");
            $.fn.yiiGridView.update("release-request-grid");
            element.html(10);
        } else {
            element.html(val);
        }
    }, 1000);
</script>
<style>
    .grid-view .filter-container input {
        max-width: 100px;
    }
</style>