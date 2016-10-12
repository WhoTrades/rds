<?php /** @var $this app\components\View */ ?>
<?php /** @var $model app\models\ReleaseRequest */ ?>
<?php /** @var $form yii\bootstrap\ActiveForm */?>
<div class="form" style="width: 400px; margin: auto">
    <?php $form = yii\bootstrap\ActiveForm::begin(array(
        'enableAjaxValidation' => true,
        'id' => 'release-request-form',
        'options' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => false,
            'beforeValidate' => 'js:function(form, data, hasError){
                $("button", $(form)).attr("disabled", true);

                return true;
            }',
            'afterValidate' => 'js:function(form, data, hasError){
                if (!hasError) {
                    $("button", $(form)).attr("disabled", true);
                    $.post($("#release-request-form").attr("action"), $("#release-request-form").serialize()).done(function(){
                        $("#release-request-form-modal").modal("hide");
                        $("button", $(form)).attr("disabled", false);
                    });
                } else {
                    $("button", $(form)).attr("disabled", false);
                }
            }',
        ),
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

    <div class="row buttons">
        <button class="btn">
            <?=$model->isNewRecord ? 'Create' : 'Save'?>
        </button>
    </div>

    <?php $form->end(); ?>

</div><!-- form -->

<script type="text/javascript">
    updateStashUri = function(){
        var projectName = $('#ReleaseRequest_rr_project_obj_id option:selected').html();
        if (!projectName) return;
        if (typeof this.ajax != 'undefined') {
            this.ajax.abort();
        }

        $('#diff-preview').html(<?=json_encode(yii\bootstrap\BaseHtml::icon(TbHtml::ICON_REFRESH))?>);

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
                $('#diff-preview').html(<?=json_encode(yii\bootstrap\BaseHtml::icon(TbHtml::ICON_STOP))?>).attr('a', '#');
            }
        });
    };
    document.onload.push(updateStashUri);
</script>