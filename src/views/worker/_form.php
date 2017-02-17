<?php
/**
 * @var $model app\models\Worker
 * @var $form yii\widgets\ActiveForm
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="form" style="width: 400px; margin: auto">

    <?php $form = ActiveForm::begin(['id' => 'worker-form']) ?>
        <p class="note">Fields with <span class="required">*</span> are required.</p>
        <div class="row">
            <?= $form->field($model, 'worker_name') ?>
        </div>
        <div class="row buttons">
            <?php echo Html::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
        </div>
    <?php ActiveForm::end() ?>

</div>
