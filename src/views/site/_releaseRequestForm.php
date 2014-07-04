<?php
/* @var $this ReleaseRequestController */
/* @var $model ReleaseRequest */
/* @var $form TbActiveForm */
?>

<div class="form">

    <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
        'type' => 'horizontal',
        'enableAjaxValidation' => false,
        'htmlOptions' => array(
            'enctype' => 'multipart/form-data',
        )
        ));
    ?>

    <p class="note">Fields with <span class="required">*</span> are required.</p>

    <?php echo $form->errorSummary($model); ?>

    <?php echo $form->textFieldRow($model,'rr_comment'); ?>

    <?php echo $form->dropDownListRow($model, 'rr_project_obj_id', \Project::model()->forList()); ?>
    <?php echo $form->dropDownListRow($model, 'rr_release_version', \ReleaseVersion::model()->forList()); ?>

    <button type="submit" class="btn btn-primary btn-large">
        <?=$model->isNewRecord ? 'Create' : 'Save'?>
    </button>

    <?php $this->endWidget(); ?>

</div><!-- form -->