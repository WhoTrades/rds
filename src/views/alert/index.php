<?/** @var $lamps array*/?>
<table>
    <?foreach ($lamps as $name => $lamp) {?>
        <tr>
            <td><?=$name?></td>
            <td><img src="/images/alarm.png" class="<?=$lamp['status'] ? 'status-on' : 'status-off'?>" /></td>
            <td>
                <?if ($lamp['status']) {?>
                    <form method="post">
                        <button name="disable[<?=$name?>]" value="1">Остановить на 10 минут</button>
                    </form>
                <?}?>
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