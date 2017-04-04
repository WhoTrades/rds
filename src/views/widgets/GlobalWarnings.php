<?php /** @var $warnings GlobalWarningItem[] */
if (!$warnings) {
    return;
} ?>

<div style="border: solid 2px #f4bb51; background: #ffcccc; font-weight: bold; padding: 5px;">
    <?php foreach ($warnings as $warning) { ?>
        <div class="warning">
            <?= yii\bootstrap\BaseHtml::icon($warning->icon) ?>
            <?= $warning->message ?>
        </div>
    <?php } ?>
</div>
