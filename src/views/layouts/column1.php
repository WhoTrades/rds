<?php /** @var $this Controller */ ?>
<?php $this->beginContent('views/layouts/main'); ?>
<div id="content">
    <?php if (!empty($this->menu)) { ?>
        <div class="row">
            <div class="span3">
                <div id="sidebar">
                    <?php
                        $this->widget('yiistrap.widgets.TbNav', ['items' => $this->menu, 'htmlOptions' => ['class' => 'operations']]);
                    ?>
                </div><!-- sidebar -->
            </div>
        </div>
    <?php } ?>
    <?php echo $content; ?>
</div><!-- content -->
<?php
$this->endContent();
