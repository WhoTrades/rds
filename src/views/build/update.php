<?php
/** @var $this BuildController */
use app\controllers\BuildController;
use app\models\Build;

/** @var $model Build */

$this->params['menu'] = array(
    array('label' => 'View Build', 'url' => array('view', 'id' => $model->obj_id)),
    array('label' => 'Manage Build', 'url' => array('admin')),
);
?>

<h1>Update Build <?php echo $model->obj_id; ?></h1>

<?php
echo $this->render('_form', array('model' => $model));
