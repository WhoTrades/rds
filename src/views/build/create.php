<?php
/** @var $model Build */

use whotrades\rds\models\Build;

$this->params['menu'] = array(
    array('label' => 'Manage Build', 'url' => array('admin')),
);
?>

<h1>Create Build</h1>

<?php echo $this->render('_form', array('model' => $model));
