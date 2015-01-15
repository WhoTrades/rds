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
            'beforeValidate' => 'js:function(form, data, hasError){
                $("button", $(form)).attr("disabled", true);

                return true;
            }',
            'afterValidate' => 'js:function(form, data, hasError){
                if (!hasError) {
                    $("button", $(form)).attr("disabled", true);
                    $.post($("#release-request-use-form").attr("action"), $("#release-request-use-form").serialize()).done(function(){
                        $("#release-request-use-form-modal").modal("hide");
                        $("button", $(form)).attr("disabled", false);
                    });
                } else {
                    $("button", $(form)).attr("disabled", false);
                }
            }',
        ),
        ));
    ?>

    <?=$form->errorSummary($model)?>

    <?if (!$releaseRequest->rr_project_owner_code_entered) {?>
        <?php echo $form->numberFieldControlGroup($model,'rr_project_owner_code'); ?>
    <?}?>

    <?php echo TbHtml::submitButton('USE'); ?>

    <?php $this->endWidget(); ?>
</div>