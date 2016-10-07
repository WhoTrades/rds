<?php
/* @var $this LogController */
/* @var $model Log */
/* @var $form CActiveForm */
?>

<div class="wide form">

    <?php $form=$this->beginWidget('CActiveForm', array(
        'action'=>\Yii::$app->createUrl($this->route),
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
        <?php echo $form->label($model,'log_user'); ?>
        <?php echo $form->textField($model,'log_user',array('size'=>60,'maxlength'=>128)); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model,'log_text'); ?>
        <?php echo $form->textArea($model,'log_text',array('rows'=>6, 'cols'=>50)); ?>
    </div>

    <div class="row buttons">
        <?php echo CHtml::submitButton('Search'); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- search-form -->