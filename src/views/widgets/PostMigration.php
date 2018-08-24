<?php /** @var $this whotrades\rds\components\View */

use yii\helpers\Html;
use yii\helpers\Url;

?>

<?php /** @var $readyPostMigrationsCount */ ?>
<?php if ($readyPostMigrationsCount) {?>
    <div style="border: solid 2px #f4bb51; background: #ffcccc; font-weight: bold; padding: 5px; margin: 5px;">
        There are <?= $readyPostMigrationsCount ?> <?= Html::a('post migrations', Url::to('/postMigration/index')) ?> to start
    </div>
<?php }
