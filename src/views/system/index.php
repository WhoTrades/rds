<?php
/** @var $this SystemController */
/** @var $config RdsDbConfig */
/** @var $form TbActiveForm */
/** @var $model StopDeploymentForm */
/** @var $config RdsDbConfig */
?>
<div class="container-fluid">
    <h1><?=$this->pageTitle?></h1>
        <?php $form = $this->beginWidget('yiistrap.widgets.TbActiveForm'); ?>
        <?php echo $form->errorSummary($model); ?>

        <?php echo $form->hiddenField($model, 'status'); ?>
        <?php if ($config->deployment_enabled) {?>
            <?php echo $form->textFieldControlGroup($model, 'reason'); ?>
            <?=TbHtml::button(
                'Отключить деплой проектов/синхронизацию конфигов',
                ['type' => 'submit', 'class' => 'btn-lg btn-danger']
            )?>
        <?php } else {?>
            <?=TbHtml::button(
                'Включить деплой проектов/синхронизацию конфигов',
                ['type' => 'submit', 'class' => 'btn-lg btn-success']
            )?>
        <?php }?>
    <?php $this->endWidget(); ?>
</div>
