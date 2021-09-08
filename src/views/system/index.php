<?php
/**
 * @var $model whotrades\rds\models\Project
 * @var $form yii\widgets\ActiveForm
 * @var $config whotrades\rds\models\RdsDbConfig
 * @var $config whotrades\rds\models\forms\StopDeploymentForm
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('rds','head_system_management');
?>

<div class="container-fluid">
    <h1><?= $this->title ?></h1>
    <?php $form = ActiveForm::begin() ?>
    <?= $form->errorSummary([$model]); ?>
    <?php if ($config->deployment_enabled) {?>
        <?= Html::activeHiddenInput($model, 'status', ['value' => 0]) ?>

        <?php echo $form->field($model, 'reason'); ?>
        <?= Html::button(
            Yii::t('rds', 'btn_disable_deploy_config_sync'),
            ['type' => 'submit', 'class' => 'btn-lg btn-danger']
        )?>
    <?php } else {?>
        <?= Html::activeHiddenInput($model, 'status', ['value' => 1]) ?>

        <?= Html::button(
            Yii::t('rds', 'btn_enable_deploy_config_sync'),
            ['type' => 'submit', 'class' => 'btn-lg btn-success']
        )?>
    <?php }?>
    <?php ActiveForm::end() ?>
</div>
