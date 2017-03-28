<?php
/**
 * @var $model Project
 * @var $form yii\widgets\ActiveForm
 */

use yii\bootstrap\Alert;
use yii\bootstrap\Html;
use yii\widgets\ActiveForm;
use app\models\Project;

?>

<script src="/css/codemirror.js"></script>
<script src="/css/php/clike.js"></script>
<script src="/css/php/php.js"></script>

<link rel="stylesheet" href="/css/codemirror.css">

<div class="form" style="width: 1200px; margin: auto">

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

        <?php foreach ($model->projectConfigs as $projectConfig) { ?>
            <h3><?=$projectConfig->pc_filename?></h3>
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
            <?= Html::textarea(
                'project_config[' . $projectConfig->pc_filename . ']',
                isset($_POST['project_config'][$projectConfig->pc_filename]) ? $_POST['project_config'][$projectConfig->pc_filename] : $projectConfig->pc_content,
                ['id' => 'php-config-code-' . $projectConfig->pc_filename]
            ) ?>
            <script type="text/javascript">
                var editor = CodeMirror.fromTextArea(
                document.getElementById("php-config-code-<?=$projectConfig->pc_filename?>"),
                {
                    lineNumbers: true,
                    matchBrackets: true,
                    mode: "application/x-httpd-php",
                    indentUnit: 4,
                    viewportMargin: Infinity,
                    indentWithTabs: true
                }
                );
            </script>
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
