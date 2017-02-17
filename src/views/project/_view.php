<?php
/* @var $data Project */
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

	<b><?php echo Html::encode($data->getAttributeLabel('project_name')); ?>:</b>
	<?php echo Html::encode($data->project_name); ?>
	<br />


</div>