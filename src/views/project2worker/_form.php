<?php
/**
 * @var $model whotrades\rds\models\Project2worker
 * @var $form yii\widgets\ActiveForm
 */

use yii\helpers\Html;
use whotrades\rds\models\Worker;
use whotrades\rds\models\Project;
use yii\widgets\ActiveForm;

?>

<div class="form" style="width: 400px; margin: auto">

    <?php $form = ActiveForm::begin(['id' => 'project2worker-form']) ?>
        <p class="note">Fields with <span class="required">*</span> are required.</p>
        <?= $form->field($model, 'worker_obj_id')->dropDownList(Worker::forList()) ?>
        <?= $form->field($model, 'project_obj_id')->dropDownList(Project::forList()) ?>
        <div class="row buttons">
            <?php echo Html::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
        </div>
    <?php ActiveForm::end() ?>

</div>
