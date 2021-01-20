<?php
/** @var $dataProvider yii\data\ActiveDataProvider */

$this->params['menu'] = array(
    array('label' => Yii::t('rds', 'btn_create_project'), 'url' => array('create')),
    array('label' => Yii::t('rds', 'head_project_management'), 'url' => array('admin')),
);
?>

<h1><?=Yii::t('rds', 'menu_projects')?></h1>

<?php $this->widget('zii.widgets.CListView', array(
    'dataProvider' => $dataProvider,
    'itemView' => '_view',
));
