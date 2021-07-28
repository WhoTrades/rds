<?php
/** @var Migration $model */
/** @var MigrationLogAggregatorUrlInterface $migrationLogAggregatorUrl */

use whotrades\rds\models\Migration;
use whotrades\RdsSystem\Migration\LogAggregatorUrlInterface as MigrationLogAggregatorUrlInterface;

echo yii\grid\GridView::widget([
   'id' => 'build-grid',
   'dataProvider' => $model->search($model->attributes),
   'filterModel' => $model,
   'options' => ['class' => 'table-responsive'],
   'rowOptions' => function ($model) {return ['class' => 'migration-' . str_replace("\\", "", $model->migration_name)];},
   'columns' => include('_migrationRow.php')
]);
