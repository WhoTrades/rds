<?/** @var $lamps array*/?>
<?
    $lampNameMap = [
        AlertLog::WTS_LAMP_NAME => '[PROD] Monitoring',
        AlertLog::CRM_LAMP_NAME => '[CRM] Monitoring',
        AlertLog::TEAM_CITY_LAMP_NAME => 'TeamCity',
        AlertLog::WTS_DEV_LAMP_NAME => '[DEV] Monitoring',
    ];
?>
<table>
    <?foreach ($lamps as $name => $lamp) {?>
        <tr>
            <td><?=$lampNameMap[$name]?> (<?=$name?>)</td>
            <td><img src="/images/alarm.png" class="<?=$lamp['status'] ? 'status-on' : 'status-off'?>" /></td>
            <td>
                <?if ($lamp['status']) {?>
                    <?= TbHtml::form() ?>
                        <?= TbHtml::submitButton('Остановить на 10 минут', [ 'name' => "disable[$name]", 'value' => AlertController::ALERT_WAIT_TIMEOUT ]) ?>
                    <?= TbHtml::endForm() ?>
                <?} else if (strtotime($lamp['timeout']) > time()) { ?>
                    <?= TbHtml::form() ?>
                        Остановлена до <?= date('H:i:s', strtotime($lamp['timeout'])) ?>
                        <?= TbHtml::submitButton('Включить', [ 'name' => "disable[$name]", 'value' => '-1 minutes' ]) ?>
                    <?= TbHtml::endForm() ?>
                <? } ?>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <?= TbHtml::form() ?>
                <table class="table table-bordered">
                    <tr>
                        <td>Ошибки</td>
                        <td>Игнорируемые</td>
                    </tr>
                    <tr>
                        <?
                            $iconMap = [
                                AlertController::ALERT_LIST_TYPE_ERRORS  => TbHtml::ICON_PAUSE,
                                AlertController::ALERT_LIST_TYPE_IGNORES => TbHtml::ICON_PLAY,
                            ];

                            $ignoreTimeMap = [
                                AlertController::ALERT_LIST_TYPE_ERRORS  => '+10 years',
                                AlertController::ALERT_LIST_TYPE_IGNORES => '-1 minutes',
                            ];
                        ?>

                        <? foreach([AlertController::ALERT_LIST_TYPE_ERRORS, AlertController::ALERT_LIST_TYPE_IGNORES] as $type) { ?>
                        <td>
                            <? foreach($lamp[$type] as $alert) {?>
                                <? /** @var AlertLog $alert */?>
                                <? $alertName = $alert->alert_name; ?>

                                <? if ($alert->hasLink()) {
                                    $alertName = TbHtml::link($alertName, $alert->getLink(), ['target' => '_blank']);
                                } ?>

                                <?= TbHtml::em(
                                    TbHtml::submitButton(yii\bootstrap\BaseHtml::icon($iconMap[$type]), [
                                        'name' => "ignore[$alert->obj_id]",
                                        'value' => $ignoreTimeMap[$type],
                                        'size' => "xs",
                                        'color' => $alert->alert_status === AlertLog::STATUS_ERROR
                                            ? TbHtml::BUTTON_COLOR_DANGER
                                            : TbHtml::BUTTON_COLOR_SUCCESS,
                                    ]) . ' ' . $alertName,
                                    [
                                        'color' => $alert->alert_status === AlertLog::STATUS_ERROR
                                            ? TbHtml::TEXT_COLOR_ERROR
                                            : TbHtml::TEXT_COLOR_SUCCESS,
                                    ]
                                )?>
                            <? } ?>
                            <? if (empty($lamp[$type])) { ?>
                                <?= TbHtml::labelTb('None', ['color' => TbHtml::LABEL_COLOR_WARNING]) ?>
                            <? } ?>
                        </td>
                        <? } ?>
                    </tr>
                </table>
                <?= TbHtml::endForm() ?>
            </td>
        </tr>
    <?}?>
</table>

<script>
    $(document).ready(function(){
        $('img.status-on').each(function(k, v){
            var max = 5;
            var min = 1;
            var current = (min+max)/2;
            console.log('aaa');
            setInterval(function(){
                var value = current > (max/2) ? max - current : current;
                $(v).css({
                    '-webkit-filter': 'saturate(' + value.toFixed(2) + ')'
                });
                current += 0.3;
                if (current > max) current = min;
                console.log("Saturation: " + value.toFixed(2));
            }, 40);
        });
    });

    setInterval(function(){location+='';}, 10000);
</script>


<style>
    img.status-off {
        -webkit-filter: saturate(0);
        filter: saturate(0);
    }
</style>