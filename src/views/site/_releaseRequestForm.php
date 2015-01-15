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

    <?php echo $form->textFieldControlGroup($model,'rr_comment'); ?>

    <?php echo $form->dropDownListControlGroup($model, 'rr_project_obj_id', \Project::model()->forList()); ?>
    <?php echo $form->dropDownListControlGroup($model, 'rr_release_version', \ReleaseVersion::model()->forList()); ?>

    <div class="row buttons">
        <?php echo TbHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->