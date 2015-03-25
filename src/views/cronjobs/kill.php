<?
if (empty($result)) {
    echo TbHtml::alert(TbHtml::ALERT_COLOR_DANGER, "<strong>Нет работающих процессов</strong>");
    return;
}
$text = "<h4>Остановка процессов</h4>";
foreach ($result as $val) {
    $text .= "<strong>{$val['server']}</strong><br />\n";
    if ($val['processes']) {
        foreach ($val['processes'] as $pid => $data) {
            $text .= "$pid ".($data['killed'] ? "<b>killed</b>" : "<b style='color: red'>NOT KILLED</b>").": {$data['command']}<br />\n";
        }
    } else {
        $text .= "<i>Процессы не найдены</i><br />";
    }
}
echo TbHtml::alert(TbHtml::ALERT_COLOR_DANGER, $text)
?>