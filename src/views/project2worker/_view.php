<?php
/** @var $data Project2worker */
use whotrades\rds\models\Project2worker;

?>

<div class="view">

    <b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('obj_id')); ?>:</b>
    <?php echo \yii\helpers\Html::a(\yii\helpers\Html::encode($data->obj_id), array('view', 'id' => $data->obj_id)); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('obj_created')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($data->obj_created); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('obj_modified')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($data->obj_modified); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('obj_status_did')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($data->obj_status_did); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('worker_obj_id')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($data->worker_obj_id); ?>
    <br/>

    <b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('project_obj_id')); ?>:</b>
    <?php echo \yii\helpers\Html::encode($data->project_obj_id); ?>
    <br/>
</div>