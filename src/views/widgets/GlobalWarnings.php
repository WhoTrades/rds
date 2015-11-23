<?/** @var $warnings GlobalWarningItem[] */?>
<?if (!$warnings) {return;}?>

<div style="border: solid 2px #f4bb51; background: #ffcccc; font-weight: bold; padding: 5px;">
    <?foreach ($warnings as $warning) {?>
        <div class="warning">
            <?=TbHtml::icon($warning->icon)?>
            <?=$warning->message?>
        </div>
    <?}?>
</div>

