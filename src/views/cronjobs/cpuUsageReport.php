<table class="items table table-hover table-bordered">
    <thead>
        <tr>
            <th>Проект</th>
            <th>Группа</th>
            <th>Тег логирования</th>
            <th>Время, сек</th>
        </tr>
    </thead>
    <tbody>
        <?foreach ($data as $val) {?>
            <tr>
                <td><?=$val['project_name']?></td>
                <td><?=$val['group']?></td>
                <td>
                    <?=$val['substring']?>
                    <?=TbHtml::tooltip(
                        TbHtml::icon(TbHtml::ICON_INFO_SIGN),
                        "", $val['command'], [
                            'onclick' => 'js:$("span.command", $(this).parent().parent()).html("'.$val['command'].'"); return false;',
                        ]
                    )
                    ?>
                    <br /><span class="command"></span>
                </td>
                <td><?=$val['round']?></td>
            </tr>
        <?}?>
    </tbody>
</table>