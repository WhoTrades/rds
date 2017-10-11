<?php
/**
 * @var $model whotrades\rds\models\Build
 * @var $form yii\widgets\ActiveForm
 */
use whotrades\rds\models\Build;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="form" style="width: 400px; margin: auto">

    <?php $form = ActiveForm::begin(['id' => 'build-form']) ?>
        <?= $form->field($model, 'build_status')->dropDownList([
            $model->build_status => $model->build_status,
            Build::STATUS_NEW => Build::STATUS_NEW,
            Build::STATUS_FAILED => Build::STATUS_FAILED,
        ]); ?>

        <div class="row buttons">
            <?php echo Html::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
        </div>
    <?php ActiveForm::end() ?>

</div><!-- form -->