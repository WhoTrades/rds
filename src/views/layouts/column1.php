<?php /* @var $this Controller */ ?>
<?php $this->beginContent('//layouts/main'); ?>
<div id="content">
	<? if ($this->menu) { ?>
		<div class="row">
			<div class="span3">
				<div id="sidebar">
					<?php
					$this->widget('yiistrap.widgets.TbNav', array(
						'items'=>$this->menu,
						'htmlOptions'=>array('class'=>'operations'),
					));
					?>
				</div><!-- sidebar -->
			</div>
		</div>
	<?}?>
	<?php echo $content; ?>
</div><!-- content -->
<?php $this->endContent(); ?>
