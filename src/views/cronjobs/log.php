<?
if (empty($result)) {
    echo TbHtml::alert(TbHtml::ALERT_COLOR_SUCCESS, "<strong>Нет работающих процессов</strong>");
    return;
}
$ok = false;
$text = "<h4>Последние строки лога</h4>";
foreach ($result as $val) {
    $text .= "<strong>{$val['server']}</strong><br />\n";
    $text .= "<pre>{$val['log']}</pre><br />";
}
echo TbHtml::alert(TbHtml::ALERT_COLOR_SUCCESS, $text);
?>