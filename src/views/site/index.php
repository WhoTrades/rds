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
    'id' => 'modal-popup',
    'header' => '',
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
        'obj_id',
        'obj_created',
        'rr_user',
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
        <h2 style="padding: 0; margin:0; float: left; margin-right: 20px;">Запрос релиза</h2>
        <?php
        yii\bootstrap\Modal::begin(array(
            'id' => 'release-request-form-modal',
            'header' => 'Запрос релиза',
            'toggleButton' => ['label' => 'Собрать проект', 'class' => 'btn'],
        ));
        echo $this->render('createReleaseRequest', ['model' => $releaseRequest['model']], true);
        yii\bootstrap\Modal::end();?>
    </div>
    <div class="col-md-8" style="float: right">
        <div class="row">
            <?php foreach ($mainProjects as $project) {?>
                <div style="float: right">
                    <?php
                    $modal = yii\bootstrap\Modal::begin(array(
                        'id' => 'release-request-form-modal',
                        'header' => 'Запрос релиза',
                        'toggleButton' => [
                            'label' => 'Собрать ' . $project->project_name, 'class' => 'btn',
                            'onclick' => "$('#releaserequest-rr_project_obj_id').val({$project->obj_id});
                            setTimeout(function(){
                                $('#release-request-form-modal .modal-body input:first').focus();
                                $('#ReleaseRequest_rr_project_obj_id').change();
                            }, 500);",
                        ],

                    ));
                    echo $this->render('createReleaseRequest', ['model' => $releaseRequest['model']], true);
                    $modal->end();
                    ?>
                </div>
            <?php }?>
        </div>
    </div>

</div>
<h2>



    <div style="float: right">

    </div>
</h2>

<div style="clear: both"></div>
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
    });
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
            $.ajax({url: this.href, data: {"ajax": 1}}).done(function(html, b, c, d){
                if (html == "using" || html == 'used') {
                    return;
                }

                showForm($(obj).attr('--data-id'));
            });
            e.preventDefault();
        });

        function showForm(id)
        {
            $.ajax({url: "/use/index/" + id}).done(function(html){
                if (html == "using") {
                    return;
                }
                $('#release-request-use-form-modal .modal-body').html($("#use-form", $(html)).html());
                $('body').append($(html).filter('script:last'))
                $("#release-request-use-form-modal").modal("show");
                setTimeout(function(){
                    $('#release-request-use-form-modal .modal-body input:first').focus();
                }, 500);
            });
        }

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
    })
</script>


<style>
    body table.table tbody tr.release-request-used {
        background: #EEFFEE;
    }
</style>
