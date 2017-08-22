<?php
/** @var $this app\components\View */
/** @var $model app\models\ReleaseRequest
/** @var $form yii\bootstrap\ActiveForm */
use yii\bootstrap\Html;

$this->registerJs('
    $("#release-request-form").on("beforeSubmit", function(e) {
        var form = $("#release-request-form"),
        btn  = form.find("button[type=\"submit\"]"),
        modal= $("#release-request-form-modal");

        btn.attr("disabled", true);

        $.post($("#release-request-form").attr("action"), $("#release-request-form").serialize()).done(function(html) {
            var qq = $("#release-request-form", $(html)).html();
            $("#release-request-form").html(qq);
            if ($("#release-request-form .error-summary").length == 0) {
                modal.modal("hide");
            }
            btn.attr("disabled", false);
        });

        return false;
    });
');
?>
<div class="form" style="margin: auto">
    <?php
        $form = yii\bootstrap\ActiveForm::begin(array(
            'id' => 'release-request-form',
        ));
    ?>

    <?php echo $form->errorSummary($model); ?>

    <?php echo $form->field($model, 'rr_comment')->textInput(); ?>

    <a href="#" title="Ссылка на stash с diff от текущей версии проекта до master" id="diff-preview" target="_blank" style="float: right">
        <?=yii\bootstrap\BaseHtml::icon('stop')?>
    </a>

    <?php echo $form->field($model, 'rr_project_obj_id')->dropDownList(
        \app\models\Project::forList(),
        ['onchange' => 'updateStashUri();']
    ); ?>

    <div style="display: none">
        <?php echo $form->field($model, 'rr_release_version')->dropDownList(\app\models\ReleaseVersion::forList()); ?>
    </div>

    <?= Html::submitButton('OK', ['class' => 'btn btn-primary'])?>

    <?php
        yii\bootstrap\ActiveForm::end();
    ?>

</div><!-- form -->

<script type="text/javascript">
    updateStashUri = function(){
        var projectName = $('#releaserequest-rr_project_obj_id option:selected').html();
        if (!projectName) return;
        if (typeof this.ajax != 'undefined') {
            this.ajax.abort();
        }

        $('#diff-preview').html(<?=json_encode(yii\bootstrap\BaseHtml::icon('refresh'))?>);

        this.ajax = $.ajax({
            url: "/api/getProjectCurrentVersion",
            data: {
                projectName: projectName
            }
        }).done(function(version){
            if (version) {
                var tag = projectName+'-'+version;
                $('#diff-preview').
                html('предпросмотр изменений').
                attr({
                    'href': 'http://git.finam.ru/projects/WT/repos/sparta/pull-requests?create&targetBranch=refs%2Ftags%2F' + tag + '&sourceBranch=refs%2Fheads%2Fmaster'
                });
            } else {
                $('#diff-preview').html(<?=json_encode(yii\bootstrap\BaseHtml::icon('stop'))?>).attr('a', '#');
            }
        });
    };
    document.onload.push(updateStashUri);
</script>