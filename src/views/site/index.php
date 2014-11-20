<?php
/* @var $this SiteController */
/* @var $releaseRejectSearchModel ReleaseReject */
/* @var $releaseRequestSearchModel ReleaseRequest */
?>

<h1>Запреты релиза</h1>
<a href="<?=$this->createUrl('createReleaseReject')?>">Создать</a>
<?php $this->widget('yiistrap.widgets.TbGridView', array(
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
            'class'=>'yiistrap.widgets.TbButtonColumn',
            'template' => '{delete}',
            'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseReject",array("id"=>$data->primaryKey))',
        ),
))); ?>
<hr />
<h2>Запрос релиза</h2>
<a href="<?=$this->createUrl('createReleaseRequest')?>">Создать</a>
<?php $this->widget('yiistrap.widgets.TbGridView', array(
    'id'=>'release-request-grid',
    'dataProvider'=>$releaseRequestSearchModel->search(),
    'filter'=>$releaseRequestSearchModel,
    'rowCssClassExpression' => function($index, $rr){
        return 'release-request-'.$rr->obj_id." release-request-".$rr->rr_status;
    },
    'columns'=>include('_releaseRequestRow.php'),
)); ?>

<script type="text/javascript">
    //an: Если не сделать обновление грида после загрузки страницы, но мы потеряем события, которые произошли после генерации страницы и до подписки на realplexor.
    // А такое случается часто, когда мы заказываем сборку
    $(document).ready(function(){
        setTimeout(function(){
            $.fn.yiiGridView.update("release-reject-grid");
            $.fn.yiiGridView.update("release-request-grid");
        }, 0);
    });
</script>

<script>
    realplexor.subscribe('releaseRequestChanged', function(event){
        var html = event.html;
        console.log($(html).find('tr.rowItem').first().attr('class'));
        var trHtmlCode = $(html).find('tr.rowItem').first().html()
        $('.release-request-'+event.rr_id).html(trHtmlCode);
        $('.release-request-'+event.rr_id).attr('class', $(html).find('tr.rowItem').first().attr('class'));
        console.log('Release request '+event.rr_id+' updated');
    });
    realplexor.subscribe('progressbarChanged', function(event){
        $('.progress-'+event.build_id+' .progress-bar').css({width: event.percent+'%'});
        $('.progress-'+event.build_id+' .progress-bar').html('<b>'+event.percent.toFixed(2).toString()+'%:</b> '+event.key);
        console.log(event);
    });
    realplexor.subscribe('refresh', function(event){
        document.location += '';
        console.log('got refresh event');
    });
    realplexor.subscribe('updateAllReleaseRequests', function(event){
        $.fn.yiiGridView.update("release-request-grid");
        console.log('got update all release requests event');
    });
    realplexor.subscribe('updateAllReleaseRejects', function(event){
        $.fn.yiiGridView.update("release-reject-grid");
        console.log('got update all release rejects event');
    });
    realplexor.execute();
</script>



<style>
    .release-request-used {
        background: #EEFFEE;
    }
</style>