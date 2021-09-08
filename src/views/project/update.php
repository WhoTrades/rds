<?php
/**
 * @var $model Project
 * @var $workers \whotrades\rds\models\Worker[]
 * @var $list bool[]
 * @var $deployment_enabled bool
 */

use whotrades\rds\models\Project;
use yii\bootstrap\Alert;

$this->params['menu'] = array(
    array('label' => Yii::t('rds', 'btn_create_project'), 'url' => array('create')),
    array('label' => Yii::t('rds', 'btn_view'), 'url' => array('view', 'id' => $model->obj_id)),
    array('label' => Yii::t('rds', 'head_project_management'), 'url' => array('admin')),
);
?>

<h1><?=Yii::t('rds', 'management')?> <?php echo $model->project_name; ?></h1>

<?php
if ($deployment_enabled) {
    echo $this->render('_form', array('model' => $model, 'list' => $list, 'workers' => $workers));
} else {
    echo Alert::widget(['options' => ['class' => 'alert-danger'], 'body' => Yii::t('rds/errors', 'prod_deploy_disabled')]);
}
