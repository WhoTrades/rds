<?php
/**
 * @var $model Project
 * @var $workers Worker[]
 * @var $list bool[]
 * @var $deployment_enabled bool
 */

use app\models\Project;
use yii\bootstrap\Alert;

$this->params['menu'] = array(
    array('label' => 'Добавить проект', 'url' => array('create')),
    array('label' => 'Просмотр', 'url' => array('view', 'id' => $model->obj_id)),
    array('label' => 'Управление проектами', 'url' => array('admin')),
);
?>

<h1>Управление <?php echo $model->project_name; ?></h1>

<?php
if ($deployment_enabled) {
    echo $this->render('_form', array('model' => $model, 'list' => $list, 'workers' => $workers));
} else {
    echo Alert::widget(['options' => ['class' => 'alert-danger'], 'body' => 'По техническим причинам деплой на PROD приостановлен']);
}
