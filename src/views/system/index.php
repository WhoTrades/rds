<?php
/**
 * @var $model app\models\Project
 * @var $form yii\widgets\ActiveForm
 * @var $config app\models\RdsDbConfig
 * @var $config app\models\forms\StopDeploymentForm
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="container-fluid">
    <h1><?= $this->pageTitle ?></h1>
    <?php $form = ActiveForm::begin() ?>
        <?= $form->errorSummary([$model]); ?>
        <?= $form->field($model, 'status')->hiddenInput() ?>
        <?php if ($config->deployment_enabled) {?>
            <?php echo $form->field($model, 'reason'); ?>
            <?= Html::button(
                'Отключить деплой проектов/синхронизацию конфигов',
                ['type' => 'submit', 'class' => 'btn-lg btn-danger']
            )?>
        <?php } else {?>
            <?= Html::button(
                'Включить деплой проектов/синхронизацию конфигов',
                ['type' => 'submit', 'class' => 'btn-lg btn-success']
            )?>
        <?php }?>
    <?php ActiveForm::end() ?>
</div>
