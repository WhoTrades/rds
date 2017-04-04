<?php
/** @var $model Project */
/** @var $deployment_enabled bool */

use app\models\Project;
use yii\bootstrap\Alert;

$this->params['menu'] = array(
    array('label' => 'Create Project', 'url' => array('create')),
    array('label' => 'View Project', 'url' => array('view', 'id' => $model->obj_id)),
    array('label' => 'Manage Project', 'url' => array('admin')),
);
?>

<h1>Update Project <?php echo $model->obj_id; ?></h1>

<?php
if ($deployment_enabled) {
    echo $this->render('_form', array('model' => $model, 'list' => $list, 'workers' => $workers));
} else {
    echo Alert::widget(['options' => ['class' => 'alert-danger'], 'body' => 'По техническим причинам деплой на PROD приостановлен']);
}
