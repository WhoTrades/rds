<?php
/** @var string $migrationName */
/** @var string $log */
?>

<h1>Log миграции <?=$migrationName ?></h1>

<?php
$maxLinesNumber = 1000;
$firstLinesNumber = 10;

$logList = explode("\n", $log);
if (count($logList) > $maxLinesNumber) {
    $logFirstLines = array_slice($logList, 0, $firstLinesNumber);
    $logLastLines = array_slice($logList, -$maxLinesNumber);

    echo "<h3>First {$firstLinesNumber} lines</h3>";
    echo "<div style='background: black; color: #AAAAAA'>" . implode('<br />', $logFirstLines) . "</div>";
    echo "<h3>Last {$maxLinesNumber} lines</h3>";
    echo "<div style='background: black; color: #AAAAAA'>" . implode('<br />', $logLastLines) . "</div>";
} else {
    echo "<div style='background: black; color: #AAAAAA'>" . implode('<br />', $logList) . "</div>";
}
