<?php /** @var $project  string */ ?>
<?php /** @var $blockers array */ ?>
<?php /** @var $blocked  array */ ?>

<?$this->pageTitle = "$project - Помощь в скрывании закрытых задач с доски";?>
<h1><?=$this->pageTitle?></h1>
<small style="color: gray">
    Тут выводятся все не скрытые задачи с доски, у которых есть fixVersion (то есть которые попали в сборку, но при этом не были полноценно убраны с доски)<br />
    По вертикали отложены закрытые задачи, которые все-равно отображается на доске, потому что в одной (или нескольких) из их fixVersion есть незакрытая задача<br />
    По горизонтали отложены не закрытые задачи с fixVersion (то есть задачи пробовали выложить, но не до конца выложили). Их нужно доводить до конца и закрывать<br />
    Ячейка означает блокирует ли задача по вертикали задачу по горизонтали. Если целая строка позеленеет - задача сама пропадет с доски и из этой страницы<br />
    Страница работает realtime (синхронно обращается к jira). Никакого кеша нет<br />
</small>
<br />
<br />

<div class="panel panel-default">
    <table border="1" class="table">
        <thead>
            <tr>
                <th rowspan="2">
                    Закрытые задачи,<br />
                    которые могут быть<br />
                    спрятаны с доски,<br />
                    если вся строка<br />
                    позеленеет<br />
                </th>
                <th colspan="<?=count($blockers['issues'])?>">Задачи, мешающие другим скрыться с доски</th>
            </tr>
            <tr>
                <?foreach ($blockers['issues'] as $ticket) {?>
                    <th><a target="_blank" href="http://jira/browse/<?=$ticket['key']?>"><?=$ticket['key']?></a></th>
                <?}?>
            </tr>
        </thead>
        <tbody>
            <?foreach ($blocked['issues'] as $ticket) {?>
                <tr>
                    <td><a target="_blank" href="http://jira/browse/<?=$ticket['key']?>"><?=$ticket['key']?></a></td>
                    <?foreach ($blockers['issues'] as $blocked) {?>
                        <?$blocks = false;?>
                        <?foreach ($blocked['fields']['fixVersions'] as $fixVersion1){ ?>
                            <?foreach ($ticket['fields']['fixVersions'] as $fixVersion2){ ?>
                                <?if ($fixVersion1['id'] == $fixVersion2['id']) {?>
                                    <?$blocks = true;?>
                                    <?break 2;?>
                                <?}?>
                            <?}?>
                        <?}?>

                        <td>
                            <?if ($blocks) {?>
                                <span style="color: red; font-size: 36px">-</span>
                            <?} else {?>
                                <span style="color: #32cd32; font-size: 24px"><b>V</b</span>
                            <?}?>
                        </td>
                    <?}?>
                </tr>
            <?}?>
        </tbody>
    </table>
</div>
