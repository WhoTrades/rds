<?php
/**
 * @var $model app\models\Log
 * @var $form yii\widgets\ActiveForm
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="wide form">

    <?php $form = ActiveForm::begin(['method' => 'GET']); ?>
        <?= $form->field($model, 'obj_id') ?>
        <?= $form->field($model, 'obj_modified') ?>
        <?= $form->field($model, 'obj_status_did') ?>
        <?= $form->field($model, 'log_user_id') ?>
        <?= $form->field($model, 'log_text') ?>
        <?= Html::submitButton('Search'); ?>
    <?php ActiveForm::end() ?>

</div>
