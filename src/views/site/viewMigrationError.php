<?php
/** @var $this View */
use whotrades\rds\models\ReleaseRequest;
use yii\web\View;

/** @var $model ReleaseRequest */
?>

<h1><?=Yii::t('rds', 'head_release_request_migration_error', [$model->obj_id])?></h1>
<pre><?=$model->rr_migration_error;?></pre>
