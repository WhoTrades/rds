<?php
/** @var $dataProvider yii\data\ActiveDataProvider */

$this->params['menu'] = array(
    array('label' => 'Create Project2worker', 'url' => array('create')),
    array('label' => 'Manage Project2worker', 'url' => array('admin')),
);
?>

<h1>Project2workers</h1>

<?php
echo $this->widget('zii.widgets.CListView', array(
    'dataProvider' => $dataProvider,
    'itemView' => '_view',
));

