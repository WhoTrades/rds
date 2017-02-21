<?php
use yii\bootstrap\Html;
use yii\bootstrap\Alert;

if (empty($result)) {
    echo Alert::widget(['options' => ['class' => 'alert-info'], 'body' => "<b>Нет работающих процессов</b>"]);

    return;
}
$ok = false;
$text = "<h4>Информация о процессах</h4>";
foreach ($result as $val) {
    $text .= "<strong>{$val['server']}</strong><br />\n";
    if ($val['processes']) {
        foreach ($val['processes'] as $pid => $val) {
            $text .= "$pid <small>(started at <b>{$val['time']}</b> NY)</small>: {$val['command']}<br />\n";
            $ok = true;
        }
    } else {
        $text .= "<i>Процессы не найдены</i> " .
            Html::a(Html::icon('info-sign'), '#', [
                'data' => ['toggle' => 'tooltip', 'placement' => 'top'],
                'title' => 'То что процессов нет при работающем кроне - это нормально. Это случается, например, если тул запускается раз в минуту и отрабатывает за 5 секунд',
            ]) . "<br />";
    }
}
echo Alert::widget(['options' => ['class' => 'alert-info'], 'body' => $text]);
