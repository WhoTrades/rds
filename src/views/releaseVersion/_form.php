<?php
/* @var $this ReleaseVersionController */
/* @var $model ReleaseVersion */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'release-version-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'rv_version'); ?>
		<?php echo $form->textField($model,'rv_version'); ?>
		<?php echo $form->error($model,'rv_version'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'rv_name'); ?>
		<?php echo $form->textField($model,'rv_name'); ?>
		<?php echo $form->error($model,'rv_name'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->