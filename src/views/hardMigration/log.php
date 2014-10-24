<?/** @var $migration HardMigration*/?>

<pre id="pre"><?=$migration->migration_log?></pre>

<div>
    <?if ($migration->canBeStopped()) {?>
        <button onclick="$.get('<?=$this->createUrl('/hardMigration/stop', ['id' => $migration->obj_id, 'returnUrl' => $_SERVER['REQUEST_URI']])?>'); $(this).hide('fast'); "><span class="icon-stop"></span>Ctrl+C</button>
    <?}?>
    <?if ($migration->canBePaused()) {?>
        <button onclick="$.get('<?=$this->createUrl('/hardMigration/pause', ['id' => $migration->obj_id, 'returnUrl' => $_SERVER['REQUEST_URI']])?>'); $(this).hide('fast');"><span class="icon-pause"></span>Pause</button><br />
    <?}?>
    <br />
</div>

<script type="text/javascript">
    realplexor.subscribe('migrationLogChunk_<?=$migration->obj_id?>', function(event){
        $('#pre').append('<span>' + event.text + '</span>');
        var span = $('#pre span:last');
        span.css({fontWeight: 'bold'});
        setTimeout(function(){
            span.css({fontWeight: 'normal'});
        }, 250);
        $('body').scrollTop($('body').height())
    });
    realplexor.execute();
    $('body').scrollTop($('body').height());
</script>
