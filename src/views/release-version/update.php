<?php
/** @var $model ReleaseVersion */

use app\models\ReleaseVersion;

$this->params['menu'] = array(
    array('label' => 'List ReleaseVersion', 'url' => array('index')),
    array('label' => 'Create ReleaseVersion', 'url' => array('create')),
    array('label' => 'View ReleaseVersion', 'url' => array('view', 'id' => $model->obj_id)),
    array('label' => 'Manage ReleaseVersion', 'url' => array('admin')),
);
?>

<h1>Update ReleaseVersion <?php echo $model->obj_id; ?></h1>

<?php echo $this->render('_form', array('model' => $model));
