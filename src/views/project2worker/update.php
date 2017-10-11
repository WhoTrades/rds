<?php
/** @var $model Project2worker */

use whotrades\rds\models\Project2worker;

$this->params['menu'] = array(
    array('label' => 'List Project2worker', 'url' => array('index')),
    array('label' => 'Create Project2worker', 'url' => array('create')),
    array('label' => 'View Project2worker', 'url' => array('view', 'id' => $model->obj_id)),
    array('label' => 'Manage Project2worker', 'url' => array('admin')),
);
?>

<h1>Update Project2worker <?php echo $model->obj_id; ?></h1>

<?php echo $this->render('_form', array('model' => $model));
