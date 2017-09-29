<?php
/** @var $this View */
use app\models\ReleaseRequest;
use yii\web\View;

/** @var $model ReleaseRequest */
?>

<h1>Запретить релиз</h1>

<?php echo $this->render('_releaseRejectForm', array('model' => $model));
