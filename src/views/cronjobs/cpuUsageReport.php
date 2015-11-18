<div class="table-responsive">
    <table class="items table table-hover table-bordered">
        <thead>
            <tr>
                <th>Проект</th>
                <th>Группа</th>
                <th>Тег логирования</th>
                <th>Время, сек</th>
                <th>Время, %</th>
            </tr>
        </thead>
        <tbody>
        <?$sum = array_reduce($data, function($carry, $val){return $carry + $val['round'];});?>
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
                    <td>
                        <span class="cpu-usage __project-<?=$val['project_name']?> __key-<?=$val['key']?>">
                            <?=round($val['round'], 2)?> сек
                        </span>
                    </td>
                    <td>
                        <?=round(100*$val['round']/$sum, 2)?>
                    </td>
                </tr>
            <?}?>
        </tbody>
    </table>
</div>

<script>
    $().ready(function() {
        webSocketSubscribe('updateToolJonPerformanceStats', function (event) {
            var obj = $('.cpu-usage.__key-' + event.key + '.__project-' + event.project);
            obj.html(event.cpuTime.toFixed(2) + ' сек');
            obj.css({'font-weight': 'bold'});
            setTimeout(function () {
                obj.css({'font-weight': 'normal'});
            }, 500);
        });
    });
</script>
