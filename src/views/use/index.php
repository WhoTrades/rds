<?/** @var $model ReleaseRequest */?>
<?/** @var $releaseRequest ReleaseRequest */?>

<div style="width: 400px; margin: auto" id="use-form">
    <?/** @var $form TbActiveForm */?>
    <?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
        'enableAjaxValidation' => true,
        'id' => 'release-request-use-form',
        'clientOptions'=>array(
            'validateOnSubmit' => true,
            'validateOnChange' => false,
            'afterValidate' => 'js:function(form, data, hasError){
            console.log(form, data, hasError);
                if (!hasError) {
                    $.post($("#release-request-use-form").attr("action"), $("#release-request-use-form").serialize()).done(function(){
                        $("#release-request-use-form-modal").modal("hide");
                    });
                }
            }',
        ),
        ));
    ?>

    <?=$form->errorSummary($model)?>

    <?if (!$releaseRequest->rr_project_owner_code_entered) {?>
        <?php echo $form->textFieldControlGroup($model,'rr_project_owner_code'); ?>
    <?}?>

    <?php echo TbHtml::submitButton('USE'); ?>

    <?php $this->endWidget(); ?>
</div>