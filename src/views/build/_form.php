<?php
/* @var $model Build */
/* @var $form TbActiveForm */
use yii\helpers\Html;
?>

<div class="form" style="width: 400px; margin: auto">

<?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
	'id'=>'build-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>


	<?php echo $form->errorSummary($model); ?>

    <?php echo $form->dropDownListControlGroup($model,'build_status', array(
        $model->build_status => $model->build_status,
        Build::STATUS_NEW => Build::STATUS_NEW,
        Build::STATUS_FAILED => Build::STATUS_FAILED,
    )); ?>

	<div class="row buttons">
		<?php echo Html::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->