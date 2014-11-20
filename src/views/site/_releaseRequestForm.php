<?php
/* @var $this ReleaseRequestController */
/* @var $model ReleaseRequest */
/* @var $form TbActiveForm */
?>

<div class="form" style="width: 400px; margin: auto">

    <?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
        'enableAjaxValidation' => false,
        'htmlOptions' => array(
            'enctype' => 'multipart/form-data',
        )
        ));
    ?>

    <p class="note">Fields with <span class="required">*</span> are required.</p>

    <?php echo $form->errorSummary($model); ?>

    <?php echo $form->textFieldControlGroup($model,'rr_comment'); ?>

    <?php echo $form->dropDownListControlGroup($model, 'rr_project_obj_id', \Project::model()->forList()); ?>
    <?php echo $form->dropDownListControlGroup($model, 'rr_release_version', \ReleaseVersion::model()->forList()); ?>

    <div class="row buttons">
        <?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->