<?php
use whotrades\rds\controllers\SiteController;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use whotrades\rds\models\ReleaseReject;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseVersion;

/** @var $this SiteController */
/** @var $model ReleaseReject */
/** @var $form ActiveForm */

?>
<div class="form" style="width: 400px; margin: auto">

    <?php $form = ActiveForm::begin(array());?>

    <?php echo $form->field($model, 'rr_comment')->textInput(); ?>
    <?php echo $form->field($model, 'rr_project_obj_id[]')->dropDownList(Project::forList(), [
        'multiple' => true,
        'style' => 'height: ' . min(300, count(Project::forList()) * 18) . 'px',
    ]); ?>

    <?php echo $form->field($model, 'rr_release_version')->dropDownList(ReleaseVersion::forList()); ?>

    <div class="row buttons">
        <?php echo Html::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
    </div>

    <?php ActiveForm::end(); ?>

</div><!-- form -->