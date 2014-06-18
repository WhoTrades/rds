<?php
/* @var $this ReleaseVersionController */
/* @var $data ReleaseVersion */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('obj_id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->obj_id), array('view', 'id'=>$data->obj_id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('obj_created')); ?>:</b>
	<?php echo CHtml::encode($data->obj_created); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('obj_modified')); ?>:</b>
	<?php echo CHtml::encode($data->obj_modified); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('obj_status_did')); ?>:</b>
	<?php echo CHtml::encode($data->obj_status_did); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('rv_version')); ?>:</b>
	<?php echo CHtml::encode($data->rv_version); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('rv_name')); ?>:</b>
	<?php echo CHtml::encode($data->rv_name); ?>
	<br />


</div>