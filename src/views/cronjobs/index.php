<?
$this->pageTitle = "Фоновые процессы";
?>
<h1><?=$this->pageTitle?></h1>
<ul class="nav nav-tabs" role="menu">
    <?foreach ($cronJobs as $val) {?>
        <li class="<?=$val['project']->project_name == $project ? 'active' : ''?>">
            <a tabindex="-1" href="?project=<?=$val['project']->project_name?>" aria-expanded="true"><?=$val['project']->project_name?></a>
        </li>
    <?}?>
</ul>

<div class="tab-content">
    <?foreach ($cronJobs as $val) {?>
        <?$group = false;?>
        <div id="tab-<?=$val['project']->project_name?>" class="tab-pane fade <?=$val['project']->project_name == $project ? 'active' : ''?> in">
            <table class="items table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Команда</th>
                        <th>Статус</th>
                        <th>Log filename</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <?foreach($val['cronJobs'] as $toolJob){?>
                    <?/** @var $toolJob ToolJob*/?>
                    <?if ($toolJob->group !== $group) {?>
                        <tr>
                            <td colspan="10">
                                <b><a href="#<?=$id = preg_replace('~\W+~', '-', $toolJob->group)?>" id="<?=$id ?>"><?=$toolJob->group?></a></b>
                            </td>
                        </tr>
                        <?$group = $toolJob->group;?>
                    <?}?>
                    <tr>
                        <td style="font-family: Menlo, Monaco, Consolas, monospace">
                            <?=preg_replace('~(--(?:tool|queue-name|event-processor)=)(\S*)~', '$1<span style="color: blue">$2</span>', preg_replace('~2>&1.*~', '', $toolJob->command))?>
                        </td>
                        <td>
                            <?if ($stopper = $toolJob->getToolJobStopped()) {?>
                                Остановлено до <?=date('H:i', strtotime($stopper->stopped_till))?>
                            <?} else {?>
                                Работает
                            <?}?>
                            <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_INFO_SIGN), $this->createUrl("/cronjobs/getInfo", [
                                'key' => $toolJob->key,
                                'project' => $val['project']->project_name
                            ]), "Запросить информацию о работающих процессах", [
                                'class' => '__get-process-info',
                                'style' => 'color: orange',
                            ])?>
                        </td>
                        <td>
                            <?$tag=str_replace('=', '\=', preg_replace('~.*logger -p \S+ -t (\S+).*~', '$1', $toolJob->command))?>
                            <input type="text"
                               value="/var/log/storelog/cronjobs/<?=$tag?>.log"
                               onclick="this.select()"
                               size="8" />
                            <a href="" style="display: none" onclick="alert('Функция ожидает websockets, так как на comet будут потери пакетов'); return false;">logs online</a>
                        </td>
                        <td>
                            <?if ($stopper) {?>
                                <a href="<?=$this->createUrl("/cronjobs/start", [
                                    'key' => $toolJob->key,
                                    'projectId' => $val['project']->obj_id,
                                    'url' => $_SERVER['REQUEST_URI'],
                                ])?>"><?=TbHtml::icon(TbHtml::ICON_PLAY)?></a>
                            <?} else {?>
                                <div style="white-space: nowrap">
                                    <?foreach (['0.7em' => '5 minutes', '1em' => '15 minutes', '1.3em' => '1 hour'] as $size => $interval) {?>
                                        <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_STOP), $this->createUrl("/cronjobs/stop", [
                                            'key' => $toolJob->key,
                                            'interval' => $interval,
                                            'projectId' => $val['project']->obj_id,
                                            'url' => $_SERVER['REQUEST_URI'],
                                        ]), "Не запускать ".$interval, ['style' => 'font-size: '.$size])?>
                                    <?}?>
                                </div>
                                <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_REMOVE_CIRCLE), $this->createUrl("/cronjobs/kill", [
                                    'key' => $toolJob->key,
                                    'project' => $val['project']->project_name
                                ]), "Мягко завершить работающие процессы (SIGTERM)", [
                                    'onclick' => 'js:if (!confirm("Вы уверены что хотите завершить процессы?")) {event.stopPropagation(); return false; }',
                                    'style' => 'font-size: '.$size,
                                    'class' => '__kill-process',
                                ])?>
                                &nbsp;
                                <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_REMOVE), $this->createUrl("/cronjobs/kill", [
                                    'key' => $toolJob->key,
                                    'signal' => 9,
                                    'project' => $val['project']->project_name
                                ]), "Жестко убить работающие процессы (sudo kill -9)", [
                                    'class' => '__kill-process',
                                    'onclick' => 'js:if (!confirm("Вы уверены что хотите жестко завершить процессы?") || prompt("Введите строку \"подтверждаю\"") != "подтверждаю") { event.stopPropagation(); return false; }',
                                    'style' => 'font-size: '.$size."; color: red",
                                ])?>
                            <?}?>
                        </td>
                    </tr>
                <?}?>
            </table>
        </div>
    <?}?>
</div>

<script type="text/javascript">
    $().ready(function(){
        $('.__get-process-info, .__kill-process').on('click', function(e){
            var td = $('td:first', $(this).parents('tr:first'));
            var that = this;
            var html = this.innerHTML;
            that.innerHTML = <?=json_encode(TbHtml::icon(TbHtml::ICON_REFRESH))?>;
            $.ajax({
                url: this.href
            }).done(function(text){
                $('.alert', td).remove();
                td.append(text);
                that.innerHTML = html;
            });

            e.preventDefault();
        });
    });
</script>