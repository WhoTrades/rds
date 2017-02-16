<?php
/** @var Controller $this */
/** @var $currentBranch - текущая активная ветка */
/** @var $allowedBranches - список всех веток, на которые можно переключиться */
/** @var $tasks - список задач, которые осталось выполнить до переключения контура */
use yii\helpers\Url;
?>
<div style="width: 400px; margin: auto;">
    <h1>Переключение контура</h1>

    <?php if (empty($tasks)) {?>
        <form method="post" action="<?=Url::to(["/pdev/"])?>">
            <?=TbHtml::hiddenField('action', 'switch')?>
            <?=TbHtml::textField('branch', $currentBranch)?>
            <?=TbHtml::help('Например, master, staging, https://jira.finam.ru/browse/WTT-1000')?>
            <br />
            <?=TbHtml::submitButton('Переключить', [
                'size' => TbHtml::BUTTON_SIZE_LARGE,
                'color' => TbHtml::BUTTON_COLOR_PRIMARY,
            ])?>
        </form>
    <?php } else {?>
        <br />
        <div>
            <?=TbHtml::alert(
                TbHtml::ALERT_COLOR_WARNING,
                TbHtml::icon(TbHtml::ICON_REFRESH) . " Контур переключается на ветку <b>$currentBranch</b>",
                ['closeText' => '']
            )?>
            <span>Не выполненные задачи переключения</span>
            <ul>
                <?php foreach ($tasks as $task) {?>
                    <li><?=$task ?></li>
                <?php }?>
            </ul>

            <script>
                setTimeout(function(){
                    document.location += '';
                }, 5000);
            </script>
        </div>
    <?php } ?>
</div>
