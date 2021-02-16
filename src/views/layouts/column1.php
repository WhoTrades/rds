<?php $this->beginContent('@app/views/layouts/main.php'); ?>
<?php
/** @var $content string */
?>
<div id="content">
    <?php if (!empty($this->params['menu'])) { ?>
        <div class="row">
            <div class="span3">
                <div id="sidebar">
                    <?php
                        echo \yii\bootstrap\Nav::widget([
                            'items'     => $this->params['menu'],
                            'options'   => ['class' => 'operations nav-pills'],
                        ]);
                    ?>
                </div><!-- sidebar -->
            </div>
        </div>
    <?php } ?>
    <?php echo $content; ?>
</div><!-- content -->
<?php
$this->endContent();
