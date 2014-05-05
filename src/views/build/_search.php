<?php
/* @var $this BuildController */
/* @var $model Build */
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
		<?php echo $form->label($model,'build_release_request_obj_id'); ?>
		<?php echo $form->textField($model,'build_release_request_obj_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'build_worker_obj_id'); ?>
		<?php echo $form->textField($model,'build_worker_obj_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'build_project_obj_id'); ?>
		<?php echo $form->textField($model,'build_project_obj_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'build_status'); ?>
		<?php echo $form->textField($model,'build_status'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'build_attach'); ?>
		<?php echo $form->textArea($model,'build_attach',array('rows'=>6, 'cols'=>50)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'build_version'); ?>
		<?php echo $form->textField($model,'build_version',array('size'=>60,'maxlength'=>64)); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->