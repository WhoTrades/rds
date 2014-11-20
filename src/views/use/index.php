<h2>Ввод кодов для релиза</h2>
<?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
        'type' => 'horizontal',
        'enableAjaxValidation' => false,
        'htmlOptions' => array(
            'enctype' => 'multipart/form-data',
        )
    ));
?>

<?if (!$releaseRequest->rr_project_owner_code_entered) {?>
    <?php echo $form->textFieldControlGroup($model,'rr_project_owner_code'); ?>
<?}?>
<?if (!$releaseRequest->rr_release_engineer_code_entered) {?>
    <?php echo $form->textFieldControlGroup($model,'rr_release_engineer_code'); ?>
<?}?>

<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>

<?php $this->endWidget(); ?>