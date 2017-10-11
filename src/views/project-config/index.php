<?php

use whotrades\rds\models\Project;
use kartik\grid\ActionColumn;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $project Project */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Локальные настройки ' . $project->project_name;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="project-config-index">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= Html::a('Добавить конфигурационный файл', Url::to(['create', 'projectId' => $project->obj_id]), ['class' => 'btn btn-success']) ?>
    </h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'pc_filename',
            [
                'class' => ActionColumn::class,
                'template' => '{update} {delete}',
            ],
        ],
    ]); ?>
</div>
