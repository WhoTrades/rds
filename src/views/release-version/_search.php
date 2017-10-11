<?php
/**
 * @var $model whotrades\rds\models\ReleaseVersion
 * @var $form yii\widgets\ActiveForm
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="wide form">

    <?php $form = ActiveForm::begin(['method' => 'GET']) ?>
    <div class="row">
        <?= $form->field($model, 'obj_id'); ?>
    </div>
    <div class="row">
        <?= $form->field($model, 'obj_created'); ?>
    </div>
    <div class="row">
        <?= $form->field($model, 'obj_modified'); ?>
    </div>
    <div class="row">
        <?= $form->field($model, 'obj_status_did'); ?>
    </div>
    <div class="row">
        <?= $form->field($model, 'rv_version'); ?>
    </div>
    <div class="row">
        <?= $form->field($model, 'rv_name'); ?>
    </div>
    <div class="row buttons">
        <?= Html::submitButton('Search'); ?>
    </div>
    <?php ActiveForm::end() ?>

</div>
