<?php
/** @var $model ReleaseVersion */

use app\models\ReleaseVersion;

$this->params['menu'] = array(
    array('label' => 'List ReleaseVersion', 'url' => array('index')),
    array('label' => 'Manage ReleaseVersion', 'url' => array('admin')),
);
?>

<h1>Create ReleaseVersion</h1>

<?php echo $this->render('_form', array('model' => $model));
