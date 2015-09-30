<?/** @var $lamps array*/?>
<table>
    <?foreach ($lamps as $name => $lamp) {?>
        <tr>
            <td><?=$name?></td>
            <td><img src="/images/alarm.png" class="<?=$lamp['status'] ? 'status-on' : 'status-off'?>" /></td>
            <td>
                <?if ($lamp['status']) {?>
                    <?= TbHtml::form() ?>
                        <?= TbHtml::submitButton('Остановить на 10 минут', [ 'name' => "disable[$name]", 'value' => 1 ]) ?>
                    <?= TbHtml::endForm() ?>
                <?}?>
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
                        <td>
                            <? foreach($lamp['errors'] as $error) {?>
                                <?= TbHtml::em(
                                    TbHtml::submitButton(TbHtml::icon(TbHtml::ICON_PAUSE), [
                                        'name' => "ignore[$error->obj_id]",
                                        'value' => "+10 years",
                                        'size' => "xs",
                                        'color' => TbHtml::BUTTON_COLOR_DANGER,
                                    ]) . ' ' .$error->alert_name,
                                    ['color' => TbHtml::TEXT_COLOR_DANGER]
                                )?>
                            <? } ?>
                            <? if (empty($lamp['errors'])) { ?>
                                <?= TbHtml::labelTb('None', ['color' => TbHtml::LABEL_COLOR_WARNING]) ?>
                            <? } ?>
                        </td>
                        <td>
                            <? foreach($lamp['ignores'] as $ignore) {?>
                                <?= TbHtml::em(
                                    TbHtml::submitButton(TbHtml::icon(TbHtml::ICON_PLAY), [
                                        'name' => "ignore[$ignore->obj_id]",
                                        'value' => "-1 minutes",
                                        'size' => "xs",
                                        'color' => $ignore->alert_status === AlertLog::STATUS_ERROR
                                                    ? TbHtml::BUTTON_COLOR_DANGER
                                                    : TbHtml::BUTTON_COLOR_SUCCESS,
                                    ]) . ' ' .$ignore->alert_name,
                                    [
                                        'color' => $ignore->alert_status === AlertLog::STATUS_ERROR
                                                    ? TbHtml::TEXT_COLOR_DANGER
                                                    : TbHtml::TEXT_COLOR_SUCCESS
                                    ]
                                )?>
                            <? } ?>
                            <? if (empty($lamp['ignores'])) { ?>
                                <?= TbHtml::labelTb('None', ['color' => TbHtml::LABEL_COLOR_WARNING]) ?>
                            <? } ?>
                        </td>
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