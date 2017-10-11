<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model whotrades\rds\models\ProjectConfig */

$this->title = 'Изменение имени локального конфигурационного файла: ' . $model->pc_filename;
$this->params['breadcrumbs'][] = ['label' => 'Project Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->obj_id, 'url' => ['view', 'id' => $model->obj_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="project-config-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
