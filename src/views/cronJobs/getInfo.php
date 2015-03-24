<?
if (empty($result)) {
    echo TbHtml::alert(TbHtml::ALERT_COLOR_INFO, "<strong>Нет работающих процессов</strong>");
    return;
}
$ok = false;
$text = "<h4>Информация о процессах</h4>";
foreach ($result as $val) {
    $text .= "<strong>{$val['server']}</strong><br />\n";
    if ($val['processes']) {
        foreach ($val['processes'] as $pid => $command) {
            $text .= "$pid: $command<br />\n";
            $ok = true;
        }
    } else {
        $text .= "<i>Процессы не найдены</i><br />";
        $text .= "<small style='padding: 5px; border: solid 1px; margin: 3px 0; display: block'>
            <span style='color: green'>".TbHtml::icon(TbHtml::ICON_INFO_SIGN)."</span>
            То что процессов нет при работающем кроне - это нормально. Это случается, например, если тул запускается раз в минуту и отрабатывает за 5 секунд
        </small><br />";
    }
}
echo TbHtml::alert(TbHtml::ALERT_COLOR_INFO, $text);
?>