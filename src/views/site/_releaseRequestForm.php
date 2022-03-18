<?php
/** @var $this whotrades\rds\components\View */
/** @var $model whotrades\rds\models\ReleaseRequest
/** @var $form yii\bootstrap\ActiveForm */
use yii\bootstrap\Html;

$this->registerJs('
    $("#release-request-form").on("beforeSubmit", function(e) {
        var form = $("#release-request-form"),
        btn  = form.find("button[type=\"submit\"]"),
        modal= $("#release-request-form-modal");

        btn.attr("disabled", true);

        $.post($("#release-request-form").attr("action"), $("#release-request-form").serialize()).done(function(html) {
            var formHtml = $("#release-request-form", $(html)).html();
            $("#release-request-form").html(formHtml);
            if ($("#release-request-form .error-summary").length == 0) {
                modal.modal("hide");
            }
            btn.attr("disabled", false);
        });

        return false;
    });
');
?>
<div class="form" style="margin: auto">
    <?php
        $form = yii\bootstrap\ActiveForm::begin(array(
            'id' => 'release-request-form',
            'action' => \yii\helpers\Url::toRoute('site/create-release')
        ));
    ?>

    <?php echo $form->errorSummary($model); ?>

    <?php echo $form->field($model, 'rr_comment')->textInput(); ?>

    <?php echo $form->field($model, 'rr_project_obj_id')->dropDownList(\whotrades\rds\models\Project::forList()); ?>

    <div style="display: none">
        <?php echo $form->field($model, 'rr_release_version')->dropDownList(\whotrades\rds\models\ReleaseVersion::forList()); ?>
    </div>

    <?= Html::submitButton('OK', ['class' => 'btn btn-primary'])?>

    <?php
        yii\bootstrap\ActiveForm::end();
    ?>

</div><!-- form -->