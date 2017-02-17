<?php
/**
 * @var $model app\models\ReleaseRequest
 * @var $releaseRequest app\models\ReleaseRequest
 * @var $form yii\widgets\ActiveForm
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<script type="application/javascript">
    $('#release-request-use-form').on('beforeValidate', function (e) {
        $('#release-request-use-form button').attr('disabled', true);
        return true;
    });

    $('#release-request-use-form').on('afterValidate', function (e) {
        if (!$('#release-request-use-form .has-error').length) {
            $('#release-request-use-form button').attr('disabled', true);
            $.post($("#release-request-use-form").attr("action"), $("#release-request-use-form").serialize()).done(function(){
                $("#release-request-use-form-modal").modal("hide");
                $('#release-request-use-form button').attr('disabled', false);
            }).error(function(e){
                console.log(e);
                $("#release-request-use-form-modal").modal("hide");
                $('#release-request-use-form button').attr('disabled', false);
            });
        } else {
            $('#release-request-use-form button').attr('disabled', false);
        }

    });
</script>

<div style="width: 400px; margin: auto" id="use-form">

    <?php $form = ActiveForm::begin([
        'enableAjaxValidation' => true,
        'id' => 'release-request-use-form',
        'validateOnSubmit' => true,
        'validateOnChange' => false,
    ]) ?>
    <?= Html::errorSummary([$model]) ?>

    <?if (!$releaseRequest->rr_project_owner_code_entered) {?>
        <?= $form->field($model,'rr_project_owner_code'); ?>
    <?}?>
    <?php echo Html::submitButton('USE'); ?>
    <?php ActiveForm::end() ?>

</div>
