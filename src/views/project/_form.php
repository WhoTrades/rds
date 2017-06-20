<?php
/**
 * @var $model Project
 * @var $workers Worker[]
 * @var $form yii\widgets\ActiveForm
 */

use conquer\codemirror\CodemirrorWidget;
use yii\bootstrap\Alert;
use yii\bootstrap\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\Project;
use app\models\Worker;
use kartik\select2\Select2;

?>

<div class="form">

    <?php $form = ActiveForm::begin(['id' => 'project-form']) ?>
        <?php if (count($model->projectConfigs)) { ?>
            <?=Alert::widget([
                'options' => [
                    'class' => 'alert-danger',
                ],
                'body' => "Внимание! Редактируя настройки, обязательно укажите в комментариях над " .
                "измененной строкой - причину и авторство. <br> Пример: // dz: поменял то-то, потому-то @since 2017-01-01",
            ])?>
        <?php } ?>
        <p class="note">Fields with <span class="required">*</span> are required.</p>
        <?= $form->field($model, 'project_name') ?>
        <?= $form->field($model, 'project_notification_email') ?>
        <?= $form->field($model, 'project_notification_subject') ?>

        <?= $form->field($model, 'projectserversarray')->widget(Select2::classname(), [
            'data' => $model->getKnownServers(),
            'options' => ['placeholder' => 'Select a servers ...', 'multiple' => true],
            'pluginOptions' => [
                'tags' => true,
                'tokenSeparators' => [',', ' '],
                'maximumInputLength' => 30,
            ],
        ]); ?>

        <h2>Локальная конфигурация <a href="<?=Url::to(['/project-config/', 'projectId' => $model->obj_id])?>">управление</a></h2>
        <?php foreach ($model->projectConfigs as $projectConfig) { ?>
            <h3>
                <?=$projectConfig->pc_filename?>
                <a href="<?=Url::to(['/project-config/update', 'id' => $projectConfig->obj_id])?>">
                    <span class="glyphicon glyphicon-pencil"></span>
                </a>
            </h3>
            <?=$model->getFirstError($projectConfig->pc_filename)
                ? Html::error(
                    $model,
                    $projectConfig->pc_filename,
                    [
                        'class' => 'alert alert-danger',
                        'encode' => false,
                    ]
                )
                : ''
            ?>
            <?=Alert::widget([
                'options' => [
                    'class' => 'alert-info',
                ],
                'body' => "F11 - полноэкранный режим редактора, Esc - выход",
            ])?>
            <?=CodemirrorWidget::widget([
                'name' => 'project_config[' . $projectConfig->pc_filename . ']',
                'value' => isset($_POST['project_config'][$projectConfig->pc_filename]) ? $_POST['project_config'][$projectConfig->pc_filename] : $projectConfig->pc_content,
                'preset' => 'php',
                'options' => [
                    'rows' => 15,
                    'style' => 'width: 100%',
                ],
            ]);?>
        <?php } ?>
        <br />
        Собирать на:<br />
        <?php foreach ($workers as $worker) {?>
            <?=Html::checkbox('workers[]', isset($list[$worker->obj_id]), array('id' => $id = uniqid(), 'value' => $worker->obj_id))?>
            <label style="display: inline" for="<?=$id?>"><?=$worker->worker_name?></label>
            <br />
        <?php }?>
        <?php if (count($model->projectConfigs)) { ?>
            <?=Alert::widget([
                'options' => [
                    'class' => 'alert-danger',
                ],
                'body' => "Внимание! Редактируя настройки, обязательно укажите в комментариях над " .
                    "измененной строкой - причину и авторство. <br> Пример: // dz: поменял то-то, потому-то @since 2017-01-01",
            ])?>
        <?php } ?>
        <br />
        <div class="row buttons">
            <?php echo Html::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
        </div>
    <?php ActiveForm::end() ?>
</div>
