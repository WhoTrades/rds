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

<?php $form=$this->beginWidget('yiistrap.widgets.TbActiveForm', array(
	'id'=>'project-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

    <?php echo $form->textFieldControlGroup($model,'project_name'); ?>
    <?php echo $form->textFieldControlGroup($model,'project_notification_email'); ?>
    <?php echo $form->textFieldControlGroup($model,'project_notification_subject'); ?>
    <?php echo $form->textAreaControlGroup($model,'project_config', [
        'style' => 'min-height: 1000px',
        'id' => 'php-config-code',
    ]); ?>


    <br />
    Собирать на:<br />
    <?foreach ($workers as $worker) {?>
        <?=CHtml::checkBox('workers[]', !empty($list[$worker->obj_id]), array('id' => $id = uniqid(), 'value' => $worker->obj_id))?>
        <label style="display: inline" for="<?=$id?>"><?=$worker->worker_name?></label>
        <br />
    <?}?>

    <br />
	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->

<script>
    var editor = CodeMirror.fromTextArea(document.getElementById("php-config-code"), {
        lineNumbers: true,
        matchBrackets: true,
        mode: "application/x-httpd-php",
        indentUnit: 4,
        viewportMargin: Infinity,
        indentWithTabs: true
    });
</script>