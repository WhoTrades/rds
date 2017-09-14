<?php
use app\models\ReleaseReject;
use app\models\ReleaseRequest;
use app\models\Project;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

/** @var $this yii\web\View */
/** @var $releaseRejectSearchModel ReleaseReject */
/** @var $releaseRequestSearchModel ReleaseRequest */
/** @var $mainProjects Project[] */
/** @var $modal yii\bootstrap\Modal */
?>

<?php
$modal = yii\bootstrap\Modal::begin(array(
    'id' => 'release-request-use-form-modal',
    'header' => 'Активировать',
))->end();
yii\bootstrap\Modal::begin(array(
    'id'            => 'modal-popup',
    'header'        => '<h4 class="modal-title"></h4>',
    'headerOptions' => ['class' => 'alert'],
))->end(); ?>

<h1>Запреты релиза</h1>
<a href="<?=yii\helpers\Url::to(['/site/create-release-reject'])?>">Создать</a>
<?php
\yii\widgets\Pjax::begin([
    'timeout' => 10000,
    'id' => 'release-reject-grid-container',
]);
echo GridView::widget(array(
    'dataProvider' => $releaseRejectSearchModel->search($releaseRejectSearchModel->attributes),
    'filterModel' => $releaseRejectSearchModel,
    'export' => false,
    'columns' => array(
        'obj_created:datetime',
        'user.email',
        'rr_comment',
        'rr_release_version',
        'project.project_name',
        array(
            'class' => yii\grid\ActionColumn::class,
            'template' => '{deleteReleaseReject}',
            'buttons' => [
                'deleteReleaseReject' => function ($url, $model, $key) {
                    $options = array_merge([
                        'title' => Yii::t('yii', 'Delete'),
                        'aria-label' => Yii::t('yii', 'Delete'),
                        'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        'data-method' => 'post',
                        'data-pjax' => '0',
                    ]);

                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, $options);
                },
            ],
        ),
    ),
));
Pjax::end();
?>
<hr />
<div class="row">
    <div class="col-md-4">
        <?php
        yii\bootstrap\Modal::begin(array(
            'id' => 'release-request-form-modal',
            'header' => 'Запрос релиза',
            //'toggleButton' => ['label' => 'Собрать проект', 'class' => 'btn'],
        ));
        echo $this->render('_releaseRequestForm', ['model' => $releaseRequest['model']], true);
        yii\bootstrap\Modal::end();
        ?>
        <h2 style="margin:0;">
            Запрос релиза
            <button type="button" accesskey="`" class="btn btn-primary" data-toggle="modal" data-target="#release-request-form-modal"
                onclick="refreshCreateReleaseRequestDialog(0)"
            >
                Собрать проект
            </button>
        </h2>
    </div>
    <div class="col-md-8" style="float: right">
        <div class="row" style="text-align: right">
            <?php foreach ($mainProjects as $key => $project) {?>
                <span data-toggle="tooltip" title="hotkey: alt+<?=$key+1?>">
                    <button type="button" accesskey="<?=$key+1?>" class="btn" data-toggle="modal" data-target="#release-request-form-modal"
                            onclick="refreshCreateReleaseRequestDialog(<?=$project->obj_id?>)">
                        Собрать <?=$project->project_name?>
                    </button>
                </span>
            <?php }?>
        </div>
    </div>

</div>

<?php

\yii\widgets\Pjax::begin(['timeout' => 10000, 'id' => 'release-request-grid-container']);
echo $this->render('_releaseRequestGrid', [
    'dataProvider'  => $releaseRequestSearchModel->search($releaseRequestSearchModel->attributes),
    'filterModel'   => $releaseRequestSearchModel,
]);
\yii\widgets\Pjax::end();
?>

<script type="text/javascript">
    // an: Если не сделать обновление грида после загрузки страницы, но мы потеряем события, которые произошли после генерации страницы и до подписки на websockets.
    // А такое случается часто, когда мы заказываем сборку
    // ar: 2 pjax одновременно не выполняются, один из них всегда возвращает "canceled", добавил таймаут - сожалею
    document.onload.push(function(){
        $.pjax.reload('#release-request-grid-container', {timeout: 10000});
        $('[data-toggle="tooltip"]').tooltip();
    });
    function refreshCreateReleaseRequestDialog(projectObjId)
    {
        $('#releaserequest-rr_project_obj_id').val(projectObjId);
        setTimeout(function(){
            $('#releaserequest-rr_comment').focus();
            $('#releaserequest-rr_project_obj_id').change();
        }, 500);
    }
</script>

<script>
    document.onload.push(function(){
        webSocketSubscribe('releaseRequestChanged', function(event){
            console.log("websocket event received", event);

            var trId = '.release-request-' + event.rr_id,
                html = $(event.html).find(trId).html();

            $(trId).html(html);

            console.log('Release request '+ event.rr_id + ' updated');
        });
        webSocketSubscribe('progressbarChanged', function(event){
            var progressBar =  $('.progress-' + event.build_id + ' .progress-bar');

            progressBar.css({width: event.percent+'%'});
            progressBar.html('<b>'+event.percent.toFixed(2).toString()+'%:</b> '+event.key);

            console.log(event);
        });
        webSocketSubscribe('refresh', function(event){
            document.location += '';
            console.log('got refresh event');
        });
        webSocketSubscribe('updateAllReleaseRequests', function(event){
            console.log("websocket event received", event);
            $.pjax.reload('#release-request-grid-container', {timeout: 10000});
            //$.pjax.reload('#release-request-grid-pjax-container', {fragment: '#release-request-grid'});
            console.log('got update all release requests event');
        });
        webSocketSubscribe('updateAllReleaseRejects', function(event){
            $.pjax.reload('#release-reject-grid-container', {timeout: 10000});
            //$.pjax.reload('#release-reject-grid-pjax-container', {fragment: '#release-reject-grid'});
            console.log('got update all release rejects event');
        });
        $('body').on('click', '.use-button', function(e){
            var obj = this;
            obj.innerHTML = '<span class="glyphicon glyphicon-refresh"></span>';
            $.ajax({url: this.href, data: {"ajax": 1}}).done(function(){});
            e.preventDefault();
        });
    })

    function popup(title, url, data)
    {
        data = data || {};
        data.ajax = 1;
        $("#modal-popup").modal("show");
        $("#modal-popup .modal-header h4").html(title);
        $("#modal-popup .modal-body").html("<center><span style='font-size: 3em'>" + <?=json_encode(yii\bootstrap\BaseHtml::icon('refresh'))?> + "<span></center>");
        $.ajax({
            url: url,
            data: data
        }).done(function(html){
            $("#modal-popup .modal-body").html(html);
        }).fail(function(a, b, c, d){
            $("#modal-popup .modal-body").html("<h1>"+ a.status + " " + c + "</h1>");
        });
    }
</script>


<style>
    body table.table tbody tr.release-request-used {
        background: #EEFFEE;
    }
</style>
