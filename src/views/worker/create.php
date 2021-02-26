<?php
/** @var $model \whotrades\rds\models\Worker */

$this->params['menu'] = array(
    array('label' => 'List Worker', 'url' => array('index')),
    array('label' => 'Manage Worker', 'url' => array('admin')),
);
?>

<h1>Create Worker</h1>

<?php
echo $this->render('_form', array('model' => $model));
