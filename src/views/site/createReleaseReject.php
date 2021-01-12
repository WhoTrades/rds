<?php
/** @var $this View */
use whotrades\rds\models\ReleaseRequest;
use yii\web\View;

/** @var $model ReleaseRequest */
?>

<h1><?=Yii::t('rds', 'head_release_lock_create')?></h1>

<?php echo $this->render('_releaseRejectForm', array('model' => $model));
