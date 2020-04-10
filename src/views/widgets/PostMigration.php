<?php /** @var $this whotrades\rds\components\View */

use yii\helpers\Html;
use yii\helpers\Url;
use whotrades\rds\models\Migration;

?>

<?php /** @var $readyPostMigrationsCount */ ?>
<?php if ($readyPostMigrationsCount) {?>
    <div style="border: solid 2px #f4bb51; background: #ffcccc; font-weight: bold; padding: 5px; margin: 5px;">
        There are <?= $readyPostMigrationsCount ?> <?= Html::a('post migrations', Url::to('/migration/index?Migration[migration_type]=' . Migration::TYPE_ID_POST . '&Migration[obj_status_did]=' . Migration::STATUS_PENDING)) ?> to start
    </div>
<?php }
