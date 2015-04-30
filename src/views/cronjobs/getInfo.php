<?
if (empty($result)) {
    echo TbHtml::alert(TbHtml::ALERT_COLOR_INFO, "<strong>Нет работающих процессов</strong>");
    return;
}
$ok = false;
$text = "<h4>Информация о процессах</h4>";
foreach ($result as $val) {
    $text .= "<strong>{$val['server']}</strong>\n";
    if ($val['processes']) {
        foreach ($val['processes'] as $pid => $command) {
            $text .= "<br />$pid: $command\n";
            $ok = true;
        }
    } else {
        $text .= "<i>Процессы не найдены</i> ".
            TbHtml::tooltip(
                TbHtml::icon(TbHtml::ICON_INFO_SIGN),
                '#',
                'То что процессов нет при работающем кроне - это нормально. Это случается, например, если тул запускается раз в минуту и отрабатывает за 5 секунд'
            )."<br />";
    }
}
echo TbHtml::alert(TbHtml::ALERT_COLOR_INFO, $text);
?>