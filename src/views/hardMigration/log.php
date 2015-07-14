<?/** @var $migration HardMigration*/?>

<pre id="pre"><?=$migration->migration_log?></pre>

<div>
    <?if ($migration->canBeStopped()) {?>
        <button onclick="$.get('<?=$this->createUrl('/hardMigration/stop', ['id' => $migration->obj_id, 'returnUrl' => $_SERVER['REQUEST_URI']])?>'); $(this).hide('fast'); "><?=TbHtml::icon(TbHtml::ICON_STOP)?>Ctrl+C</button>
    <?}?>
    <?if ($migration->canBePaused()) {?>
        <button onclick="$.get('<?=$this->createUrl('/hardMigration/pause', ['id' => $migration->obj_id, 'returnUrl' => $_SERVER['REQUEST_URI']])?>'); $(this).hide('fast');"><?=TbHtml::icon(TbHtml::ICON_PAUSE)?></span>Pause</button><br />
    <?}?>
    <br />
</div>

<script type="text/javascript">
    webSocketSubscribe('migrationLogChunk_<?=str_replace("/", "", $migration->migration_name)?>_<?=$migration->migration_environment?>', function(event){
        $('#pre').append('<span>' + event.text + '</span>');
        var span = $('#pre span:last');
        span.css({fontWeight: 'bold'});
        setTimeout(function(){
            span.css({fontWeight: 'normal'});
        }, 250);
        $('body').scrollTop($('body').height())
    });
    $('body').scrollTop($('body').height());
</script>
