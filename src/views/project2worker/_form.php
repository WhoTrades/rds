<?php
/* @var $this Project2workerController */
/* @var $model Project2worker */
/* @var $form CActiveForm */
?>

<div class="form" style="width: 400px; margin: auto">

<?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
	'id'=>'project2worker-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

    <?php echo $form->dropDownListControlGroup($model, 'worker_obj_id', \Worker::forList()); ?>
    <?php echo $form->dropDownListControlGroup($model, 'project_obj_id', \Project::forList()); ?>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->