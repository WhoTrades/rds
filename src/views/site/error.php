<?php
/* @var $this SiteController */
/* @var $error array */

$this->title=\Yii::$app->name . ' - Error';
$this->breadcrumbs=array(
	'Error',
);
?>

<h2>Error <?php echo $code; ?></h2>

<div class="error">
<?php echo \yii\helpers\Html::encode($message); ?>
</div>