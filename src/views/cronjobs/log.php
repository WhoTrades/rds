<?php
use yii\bootstrap\Alert;

if ($plainText) {
    header("Content-type: text/plain");
    foreach ($result as $val) {
        echo $val['log'] . PHP_EOL;
    }
} else {

if (empty($result)) {
    echo Alert::widget(['options' => ['class' => 'alert-success'], 'body' => "<b>Нет работающих процессов сборщиков логов</b>"]);
    return;
}
$ok = false;
$text = "<h4>Последние строки лога</h4>";
foreach ($result as $val) {
    $text .= "<strong>{$val['server']}</strong><br />\n";
    $text .= "<pre style='white-space: normal'>".nl2br(htmlspecialchars($val['log']))."</pre><br />";
}
echo Alert::widget(['options' => ['class' => 'alert-success'], 'body' => $text]);
}
