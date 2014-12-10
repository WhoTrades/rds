<?php
/* @var $this DeveloperController */
/* @var $model Developer */
/* @var $form CActiveForm */
?>

<div class="form" style="width: 400px; margin: auto">

<?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
	'id'=>'developer-form',
	'enableAjaxValidation'=>false,
)); ?>
    <?/** @var $form \TbActiveForm */?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

    <?php echo $form->textFieldControlGroup($model,'whotrades_email',array('size'=>60,'maxlength'=>64, 'append' => '@whotrades.org',)); ?>

    <?php echo $form->textFieldControlGroup($model,'finam_email',array('size'=>60,'maxlength'=>64, 'append' => '@corp.finam.ru')); ?>

    <?=TbHtml::button($model->isNewRecord ? "Create" : "Save", ['type' => 'submit', 'size' => TbHtml::BUTTON_SIZE_LARGE])?>

<?php $this->endWidget(); ?>

</div><!-- form -->