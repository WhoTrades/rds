<?php
/* @var $data Build */
use yii\helpers\Html;
?>

<div class="view">

	<b><?php echo Html::encode($data->getAttributeLabel('obj_id')); ?>:</b>
	<?php echo Html::a(Html::encode($data->obj_id), array('view', 'id'=>$data->obj_id)); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('obj_created')); ?>:</b>
	<?php echo Html::encode($data->obj_created); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('obj_modified')); ?>:</b>
	<?php echo Html::encode($data->obj_modified); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('obj_status_did')); ?>:</b>
	<?php echo Html::encode($data->obj_status_did); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('build_release_request_obj_id')); ?>:</b>
	<?php echo Html::encode($data->build_release_request_obj_id); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('build_worker_obj_id')); ?>:</b>
	<?php echo Html::encode($data->build_worker_obj_id); ?>
	<br />

	<b><?php echo Html::encode($data->getAttributeLabel('build_project_obj_id')); ?>:</b>
	<?php echo Html::encode($data->build_project_obj_id); ?>
	<br />

	<?php /*
	<b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('build_status')); ?>:</b>
	<?php echo \yii\helpers\Html::encode($data->build_status); ?>
	<br />

	<b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('build_attach')); ?>:</b>
	<?php echo \yii\helpers\Html::encode($data->build_attach); ?>
	<br />

	<b><?php echo \yii\helpers\Html::encode($data->getAttributeLabel('build_version')); ?>:</b>
	<?php echo \yii\helpers\Html::encode($data->build_version); ?>
	<br />

	*/ ?>

</div>