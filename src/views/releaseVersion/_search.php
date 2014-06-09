<?php
/* @var $this ReleaseVersionController */
/* @var $model ReleaseVersion */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'obj_id'); ?>
		<?php echo $form->textField($model,'obj_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'obj_created'); ?>
		<?php echo $form->textField($model,'obj_created'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'obj_modified'); ?>
		<?php echo $form->textField($model,'obj_modified'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'obj_status_did'); ?>
		<?php echo $form->textField($model,'obj_status_did'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'rv_version'); ?>
		<?php echo $form->textField($model,'rv_version'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'rv_name'); ?>
		<?php echo $form->textField($model,'rv_name'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->