<?php
/** @var $this whotrades\rds\components\View */
/** @var $model whotrades\rds\models\ReleaseRequest
/** @var $form yii\bootstrap\ActiveForm */
use yii\bootstrap\Html;

$this->registerJs('
    $("#release-request-form").on("beforeSubmit", function(e) {
        var form = $("#release-request-form"),
        btn  = form.find("button[type=\"submit\"]"),
        modal= $("#release-request-form-modal");

        btn.attr("disabled", true);

        $.post($("#release-request-form").attr("action"), $("#release-request-form").serialize()).done(function(html) {
            var formHtml = $("#release-request-form", $(html)).html();
            $("#release-request-form").html(formHtml);
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
            'action' => \yii\helpers\Url::toRoute('site/create-release')
        ));
    ?>

    <?php echo $form->errorSummary($model); ?>

    <?php echo $form->field($model, 'rr_comment')->textInput(); ?>

    <a href="#" title="<?=Yii::t('rds', 'link_stash_diff')?>" id="diff-preview" target="_blank" style="float: right">
        <?=yii\bootstrap\BaseHtml::icon('stop')?>
    </a>

    <?php echo $form->field($model, 'rr_project_obj_id')->dropDownList(
        \whotrades\rds\models\Project::forList(),
        ['onchange' => 'updateStashUri();']
    ); ?>

    <div style="display: none">
        <?php echo $form->field($model, 'rr_release_version')->dropDownList(\whotrades\rds\models\ReleaseVersion::forList()); ?>
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
                html(<?=Yii::t('rds', 'btn_preview_changes')?>).
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