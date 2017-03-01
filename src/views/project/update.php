<?php
/* @var $this ProjectController */
/* @var $model Project */
/* @var $deployment_enabled bool */

$this->breadcrumbs = array(
    'Projects' => array('index'),
    $model->obj_id => array('view', 'id' => $model->obj_id),
    'Update',
);

$this->menu = array(
    array('label' => 'Create Project', 'url' => array('create')),
    array('label' => 'View Project', 'url' => array('view', 'id' => $model->obj_id)),
    array('label' => 'Manage Project', 'url' => array('admin')),
);
?>

<h1>Update Project <?php echo $model->obj_id; ?></h1>

<?=TbHtml::alert(TbHtml::ALERT_COLOR_DANGER, "Внимание! Редактируя настройки, обязательно укажите в комментариях над измененной строкой - причину и авторство")?>

<?php
if ($deployment_enabled) {
    echo $this->renderPartial('_form', array('model' => $model, 'list' => $list, 'workers' => $workers));
} else {
    echo TbHtml::alert(TbHtml::ALERT_COLOR_DANGER, "По техническим причинам деплой на PROD приостановлен");
}

