<? /* @var $this ReleaseRequestController */ ?>
<? /* @var $model ReleaseRequest */ ?>
<? /* @var $form TbActiveForm */ ?>
<div class="form" style="width: 400px; margin: auto">
    <?/** @var $form TbActiveForm */?>
    <?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
        'enableAjaxValidation' => true,
        'id' => 'release-request-form',
        'clientOptions'=>array(
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

    <?php echo $form->textFieldControlGroup($model, 'rr_comment'); ?>

    <a href="#" title="Ссылка на stash с diff от текущей версии проекта до master" id="diff-preview" target="_blank" style="float: right"><?=TbHtml::icon(TbHtml::ICON_STOP)?></a>

    <?php echo $form->dropDownListControlGroup(
        $model,
        'rr_project_obj_id',
        \Project::model()->forList(),
        [
            'onchange' => 'updateStashUri();',
        ]
    ); ?>



    <?php echo $form->dropDownListControlGroup($model, 'rr_release_version', \ReleaseVersion::model()->forList()); ?>

    <div class="row buttons">
        <?php echo TbHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->

<script>
    updateStashUri = function(){
        var projectName = $('#ReleaseRequest_rr_project_obj_id option:selected').html();
        if (!projectName) return;
        if (typeof this.ajax != 'undefined') {
            this.ajax.abort();
        }

        $('#diff-preview').html(<?=json_encode(TbHtml::icon(TbHtml::ICON_REFRESH))?>);

        this.ajax = $.ajax({
            url: "/api/getProjectCurrentVersion",
            data: {
                projectName: projectName
            }
        }).done(function(version){
            if (version) {
                var tag = projectName+'-'+version;
                $('#diff-preview').
                html('diff preview').
                attr({
                    'href': 'http://stash.finam.ru/projects/WT/repos/sparta/pull-requests?create&targetBranch=refs%2Ftags%2F' + tag + '&sourceBranch=refs%2Fheads%2Fmaster'
                });
            } else {
                $('#diff-preview').html(<?=json_encode(TbHtml::icon(TbHtml::ICON_STOP))?>).attr('a', '#');
            }
        });
    }
    updateStashUri();
</script>