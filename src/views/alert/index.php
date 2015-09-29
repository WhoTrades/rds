<?/** @var $lamps array*/?>
<table>
    <?foreach ($lamps as $name => $lamp) {?>
        <tr>
            <td><?=$name?></td>
            <td><img src="/images/alarm.png" class="<?=$lamp['status'] ? 'status-on' : 'status-off'?>" /></td>
            <td>
                <?if ($lamp['status']) {?>
                    <form method="post">
                        <button type="submit" name="disable[<?=$name?>]" value="1" class="btn btn-default">
                            Остановить на 10 минут
                        </button>
                    </form>
                <?}?>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <table class="table table-bordered">
                    <tr>
                        <td>Errors</td>
                        <td>Ignore</td>
                    </tr>
                    <tr>
                        <td>
                            <form method="post">
                            <? foreach($lamp['errors'] as $error) {?>
                                <p class="text-danger">
                                    <button type="submit" name="ignore[<?= $error->obj_id ?>]" value="+10 years" class="btn btn-xs glyphicon glyphicon-pause"></button>
                                    <?= $error->alert_name?>
                                </p>
                            <? } ?>
                            </form>
                            <? if (empty($lamp['errors'])) { ?>
                                <p class="label label-warning">None</p>
                            <? } ?>
                        </td>
                        <td>
                            <form method="post">
                            <? foreach($lamp['ignores'] as $ignore) {?>
                                <p class="text-<?= $ignore->alert_status === AlertLog::STATUS_ERROR ? 'danger' : 'success'?>">
                                    <button type="submit" name="ignore[<?= $ignore->obj_id ?>]" value="-1 minutes" class="btn btn-xs glyphicon glyphicon-play"></button>
                                    <?= $ignore->alert_name?>
                                    <? /* (до <span class="label label-info"><?= $ignore->alert_ignore_timeout ?></span>) */ ?>
                                </p>
                            <? } ?>
                            </form>
                            <? if (empty($lamp['ignores'])) { ?>
                                <p class="label label-warning">None</p>
                            <? } ?>
                        </td>
                    </tr>
                </table>
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