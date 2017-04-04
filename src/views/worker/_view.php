<?php
/** @var $data app\models\Worker */
?>

<div class="view">

    <b><?php echo \yii\helpers\Html::encode($model->getAttributeLabel('obj_id')); ?>:</b>
    <?php echo \yii\helpers\Html::a(\yii\helpers\Html::encode($model->obj_id), array('view', 'id' => $data->obj_id)); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($model->getAttributeLabel('obj_created')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($model->obj_created); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($model->getAttributeLabel('obj_modified')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($model->obj_modified); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($model->getAttributeLabel('obj_status_did')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($model->obj_status_did); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($model->getAttributeLabel('worker_name')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($model->worker_name); ?>
    <br/>
</div>
