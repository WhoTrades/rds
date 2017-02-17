<?php
/**
 * @var $model app\models\Build
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
            <?= $form->field($model, 'build_release_request_obj_id'); ?>
        </div>
        <div class="row">
            <?= $form->field($model, 'build_worker_obj_id'); ?>
        </div>
        <div class="row">
            <?= $form->field($model, 'build_project_obj_id'); ?>
        </div>
        <div class="row">
            <?= $form->field($model, 'build_status'); ?>
        </div>
        <div class="row">
            <?= $form->field($model, 'build_attach'); ?>
        </div>
        <div class="row">
            <?= $form->field($model, 'build_version')->textInput(['size' => 60, 'maxlength' => 64]); ?>
        </div>
        <div class="row buttons">
            <?= Html::submitButton('Search'); ?>
        </div>
    <?php ActiveForm::end() ?>

</div>
