<?php
/* @var $this ProjectController */
/* @var $model Project */
/* @var $form TbActiveForm */
?>

<script src="/css/codemirror.js"></script>
<script src="/css/php/clike.js"></script>
<script src="/css/php/php.js"></script>

<link rel="stylesheet" href="/css/codemirror.css">

<div class="form" style="width: 1200px; margin: auto">
    <?php if (count($model->projectConfigs)) { ?>
        <?=TbHtml::alert(TbHtml::ALERT_COLOR_DANGER, "Внимание! Редактируя настройки, обязательно укажите в комментариях над
            измененной строкой - причину и авторство. <br> Пример: // dz: поменял то-то, потому-то @since 2017-01-01")?>
    <?php } ?>
<?php $form = $this->beginWidget('yiistrap.widgets.TbActiveForm', array(
    'id' => 'project-form',
    'enableAjaxValidation' => false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

    <?php echo $form->textFieldControlGroup($model, 'project_name'); ?>
    <?php echo $form->textFieldControlGroup($model, 'project_notification_email'); ?>
    <?php echo $form->textFieldControlGroup($model, 'project_notification_subject'); ?>
    <?php foreach ($model->projectConfigs as $projectConfig) { ?>
        <h3><?=$projectConfig->pc_filename?></h3>
        <?=TbHtml::error($model, $projectConfig->pc_filename, ['class' => 'alert alert-danger'])?>
        <?=TbHtml::textAreaControlGroup(
            'project_config[' . $projectConfig->pc_filename . ']',
            isset($_POST['project_config'][$projectConfig->pc_filename]) ? $_POST['project_config'][$projectConfig->pc_filename] : $projectConfig->pc_content,
            ['id' => 'php-config-code-' . $projectConfig->pc_filename]
        ); ?>

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
    <?foreach ($workers as $worker) {?>
        <?=CHtml::checkBox('workers[]', !empty($list[$worker->obj_id]), array('id' => $id = uniqid(), 'value' => $worker->obj_id))?>
        <label style="display: inline" for="<?=$id?>"><?=$worker->worker_name?></label>
        <br />
    <?}?>

    <br />
    <?php if (count($model->projectConfigs)) { ?>
        <?=TbHtml::alert(TbHtml::ALERT_COLOR_DANGER, "Внимание! Редактируя настройки, обязательно укажите в комментариях над
            измененной строкой - причину и авторство. <br> Пример: // dz: поменял то-то, потому-то @since 2017-01-01")?>
    <?php } ?>
    <div class="row buttons" style="margin-bottom: 50px;">
		<?php echo TbHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->

