<?php
/**
 * @var $model whotrades\rds\models\ReleaseVersion
 * @var $form yii\widgets\ActiveForm
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="form" style="width: 400px; margin: auto">

    <?php $form = ActiveForm::begin(['id' => 'project2worker-form']) ?>
        <p class="note">Fields with <span class="required">*</span> are required.</p>
        <?= $form->field($model, 'rv_version') ?>
        <?= $form->field($model, 'rv_name') ?>
        <div class="row buttons">
            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
        </div>
    <?php ActiveForm::end() ?>

</div>
