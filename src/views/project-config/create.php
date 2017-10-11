<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model whotrades\rds\models\ProjectConfig */

$this->title = 'Create Project Config';
$this->params['breadcrumbs'][] = ['label' => 'Project Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-config-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
