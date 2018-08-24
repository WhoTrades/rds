<?php
/** @var $model whotrades\rds\models\PostMigration */
/** @var $postMigrationAllowTimestamp string */

use whotrades\rds\models\PostMigration;
use yii\helpers\Html;
use yii\helpers\Url;

?>

<?= yii\grid\GridView::widget([
    'id' => 'build-grid',
    'dataProvider' => $model->search($model->attributes),
    'filterModel' => $model,
    'options' => ['class' => 'table-responsive'],
    'columns' => [
      'obj_id',
      'obj_created:datetime',
      'pm_name',
      'project.project_name',
      'releaseRequest.rr_build_version',
      [
          'value' => function(PostMigration $postMigration) use ($postMigrationAllowTimestamp) {
            $lines = [];
            if ($postMigration->pm_status === PostMigration::STATUS_APPLIED) {
                $lines[] = 'Applied';
            } elseif ($postMigration->pm_status === PostMigration::STATUS_STARTED) {
                $lines[] = 'Started';
            } else {
                $waitingTime = (new DateTime($postMigration->obj_created))->getTimestamp() - $postMigrationAllowTimestamp;
                if ($waitingTime > 0) {
                    $waitingDays = ceil($waitingTime / (24 * 60 * 60));

                    $lines[] = "Waiting {$waitingDays} days";
                } else {
                    if ($postMigration->pm_status === PostMigration::STATUS_FAILED) {
                        $lines[] = '<span style="color:red">' . yii\bootstrap\BaseHtml::icon('warning-sign') . ' Failed</span>';
                        $startText = 'Restart';
                    } else {
                        $startText = 'Start';
                    }

                    $lines[] = Html::a($startText, Url::to('/postMigration/apply?postMigrationId=' . $postMigration->obj_id));
                }
            }

            if ($postMigration->pm_log) {
                $lines[] = Html::a(
                    "Veiw log <img src='/images/open_new_window.png' alt='Open new window' style='margin-left:5px; margin-bottom:3px; width:13px;height:13px;'>",
                    Url::to('/postMigration/viewLog?postMigrationId=' . $postMigration->obj_id),
                    ['target' => '_blank']
                );
            }

            return implode("<br />", $lines);
          },
          'format' => 'raw',
          'header' => 'Status / Action',
      ],
    ],
]);


