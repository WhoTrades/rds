<?php
/** @var $this SiteController */
use app\models\ReleaseReject;
use app\models\ReleaseRequest;
use yii\bootstrap\Modal;

/** @var $releaseRejectSearchModel ReleaseReject */
/** @var $releaseRequestSearchModel ReleaseRequest */
?>

<?php $this->widget('yiistrap.widgets.TbModal', array(
    'id' => 'release-request-form-modal',
    'header' => 'Запрос релиза',
    'content' => $this->render('createReleaseRequest', ['model' => $releaseRequest['model']], true),
)); ?>
<?php $this->widget('yiistrap.widgets.TbModal', array(
    'id' => 'release-request-use-form-modal',
    'header' => 'Активировать',
    'content' => '',
)); ?>
<?php $this->widget('yiistrap.widgets.TbModal', array(
    'id' => 'modal-popup',
    'header' => '',
    'content' => '',
    'footer' => [
        TbHtml::button('Close', array('data-dismiss' => 'modal')),
    ],
)); ?>


<h1>Запреты релиза</h1>
<a href="<?= $this->createUrl('createReleaseReject') ?>">Создать</a>
<?php $this->widget('yiistrap.widgets.TbGridView', array(
    'id' => 'release-reject-grid',
    'dataProvider' => $releaseRejectSearchModel->search(),
    'filter' => $releaseRejectSearchModel,
    'ajaxUpdateError' => 'function(xhr,ts,et,err){ console.log(err); }',
    'htmlOptions' => ['class' => 'table-responsive'],
    'columns' => array(
        'obj_id',
        'obj_created',
        'rr_user',
        'rr_comment',
        'rr_release_version',
        'project.project_name',
        array(
            'class' => 'yiistrap.widgets.TbButtonColumn',
            'template' => '{delete}',
            'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseReject",array("id"=>$data->primaryKey))',
        ),
    ),
)); ?>
<hr/>
<div class="row">
    <div class="col-md-4">
        <h2 style="padding: 0; margin:0; float: left; margin-right: 20px;">Запрос релиза</h2>
        <?php echo TbHtml::button('Собрать проект', array(
            'data-toggle' => 'modal',
            'data-target' => '#release-request-form-modal',
        )); ?>
    </div>
    <div class="col-md-8" style="float: right">
        <div class="row">
            <?php foreach ($mainProjects as $project) { ?>
                <div style="float: right">
                    <?php /** @var $project Project */ ?>
                    <?php echo TbHtml::button('Собрать ' . $project->project_name, array(
                        'data-toggle' => 'modal',
                        'data-target' => '#release-request-form-modal',
                        'onclick' => "$('#ReleaseRequest_rr_project_obj_id').val({$project->obj_id});
                            setTimeout(function(){
                                $('#release-request-form-modal .modal-body input:first').focus();
                                $('#ReleaseRequest_rr_project_obj_id').change();
                            }, 500);",
                    )); ?>
                    &nbsp;
                </div>
            <?php } ?>
        </div>
    </div>

</div>
<h2>


    <div style="float: right">

    </div>
</h2>

<div style="clear: both"></div>
<?php $this->widget('yiistrap.widgets.TbGridView', array(
    'id' => 'release-request-grid',
    'htmlOptions' => ['class' => 'table-responsive'],
    'dataProvider' => $releaseRequestSearchModel->search(),
    'filter' => $releaseRequestSearchModel,
    'ajaxUpdateError' => 'function(xhr,ts,et,err){ console.log(err); }',
    'rowCssClassExpression' => function ($index, $rr) {
        return 'release-request-' . $rr->obj_id . " release-request-" . $rr->rr_status;
    },
    'columns' => include('_releaseRequestRow.php'),
)); ?>

<script type="text/javascript">
    //an: Если не сделать обновление грида после загрузки страницы, но мы потеряем события, которые произошли после генерации страницы и до подписки на websockets.
    // А такое случается часто, когда мы заказываем сборку
    $(document).ready(function () {
        setTimeout(function () {
            $.fn.yiiGridView.update("release-reject-grid");
            $.fn.yiiGridView.update("release-request-grid");
        }, 0);
    });
</script>

<script>
    webSocketSubscribe('releaseRequestChanged', function (event) {
        console.log("websocket event received", event);
        var html = event.html;
        console.log($(html).find('tr.rowItem').first().attr('class'));
        var trHtmlCode = $(html).find('tr.rowItem').first().html()
        $('.release-request-' + event.rr_id).html(trHtmlCode);
        $('.release-request-' + event.rr_id).attr('class', $(html).find('tr.rowItem').first().attr('class'));
        console.log('Release request ' + event.rr_id + ' updated');
    });
    webSocketSubscribe('progressbarChanged', function (event) {
        $('.progress-' + event.build_id + ' .progress-bar').css({width: event.percent + '%'});
        $('.progress-' + event.build_id + ' .progress-bar').html('<b>' + event.percent.toFixed(2).toString() + '%:</b> ' + event.key);
        console.log(event);
    });
    webSocketSubscribe('refresh', function (event) {
        document.location += '';
        console.log('got refresh event');
    });
    webSocketSubscribe('updateAllReleaseRequests', function (event) {
        console.log("websocket event received", event);
        $.fn.yiiGridView.update("release-request-grid");
        console.log('got update all release requests event');
    });
    webSocketSubscribe('updateAllReleaseRejects', function (event) {
        $.fn.yiiGridView.update("release-reject-grid");
        console.log('got update all release rejects event');
    });
</script>
<script>
    $('body').on('click', '.use-button', function (e) {
        var obj = this;
        $.ajax({url: this.href, data: {"ajax": 1}}).done(function (html, b, c, d) {
            if (html == "using" || html == 'used') {
                return;
            }

            showForm($(obj).attr('--data-id'));
        }).error(function (a, b, c) {
            console.log(a, b, c);
            $("#modal-popup .modal-header h4").html(a.status + " " + c);
            $("#modal-popup .modal-body").html("<pre>" + a.responseText + "</pre>");
            $("#modal-popup").modal("show");
        });
        e.preventDefault();
    });

    function showForm(id) {
        $.ajax({url: "/use/index/" + id}).done(function (html) {
            if (html == "using") {
                return;
            }
            $('#release-request-use-form-modal .modal-body').html($("#use-form", $(html)).html());
            $('body').append($(html).filter('script:last'))
            $("#release-request-use-form-modal").modal("show");
            setTimeout(function () {
                $('#release-request-use-form-modal .modal-body input:first').focus();
            }, 500);
        });
    }

    function popup(title, url, data) {
        data = data || {};
        data.ajax = 1;
        $("#modal-popup").modal("show");
        $("#modal-popup .modal-header h4").html(title);
        $("#modal-popup .modal-body").html("<center><span style='font-size: 3em'>" + <?=json_encode(TbHtml::icon(TbHtml::ICON_REFRESH))?> +"<span></center>");
        $.ajax({
            url: url,
            data: data
        }).done(function (html) {
            $("#modal-popup .modal-body").html(html);
        }).fail(function (a, b, c) {
            $("#modal-popup .modal-body").html("<h1>" + a.status + " " + c + "</h1>");
        });
    }
</script>


<style>
    .release-request-used {
        background: #EEFFEE;
    }
</style>
