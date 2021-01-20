<?php
/** @var $model Project */

use whotrades\rds\models\Project;

$this->params['menu'] = array(
    array('label' => Yii::t('rds', 'head_project_management'), 'url' => array('admin')),
);
?>

    <h1><?=Yii::t('rds', 'head_project_create')?></h1>

<?php echo $this->render('_form', array('model' => $model, 'list' => $list, 'workers' => $workers));
