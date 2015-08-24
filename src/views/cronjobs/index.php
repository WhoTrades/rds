<?
$this->pageTitle = "Фоновые процессы";
?>
<h1>
    <?=$this->pageTitle?>
    <small><a href="<?=$this->createUrl("cpuUsageReport")?>">CPU usage report</a></small>
    <img src="https://stm-graphite.whotrades.com/render/?width=300&height=60&from=-24hours&target=summarize(sumSeries(stats.gauges.rds.main.system.COMON.tool.*.timeCpu)%2C%2215min%22)&graphOnly=true&yMin=0" style="border: solid 1px #eee" />
</h1>
<div style="clear: both"></div>

<ul class="nav nav-tabs" role="menu">
    <?foreach ($cronJobs as $val) {?>
        <li class="<?=$val['project']->project_name == $project ? 'active' : ''?>">
            <a tabindex="-1" href="?project=<?=$val['project']->project_name?>" aria-expanded="true"><?=$val['project']->project_name?></a>
        </li>
    <?}?>

    <li style="float: right">
        <?=TbHtml::button("Обнулить показатели CPU<br /><small>Последнее обнуление: <span>".($cpuUsageLastTruncate ? date('d.m.Y H:i', strtotime($cpuUsageLastTruncate)) : "никогда")."</span></small>", [
            'color' => TbHtml::BUTTON_COLOR_DANGER,
            'onclick' => 'js:if (confirm("Вы уверены что хотите обнулить все показатели по использованию CPU")) {
                var html = this.innerHTML;
                this.innerHTML = '.json_encode(TbHtml::icon(TbHtml::ICON_REFRESH)).';
                this.disabled = true;
                var obj = this;
                $.ajax({url: "'.$this->createUrl('truncateCpuUsage').'"}).done(function(){
                    obj.innerHTML = html;
                    obj.disabled = false;
                    location += "";
                });
            }',
        ])?>
    </li>
</ul>

<div class="tab-content">
    <?foreach ($cronJobs as $val) {?>
        <?$group = false;?>
        <div id="tab-<?=$val['project']->project_name?>" class="tab-pane fade <?=$val['project']->project_name == $project ? 'active' : ''?> in">
            <table class="items table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Команда</th>
                        <th style=" width: 120px;">Последний запуск</th>
                        <th>Статус</th>
                        <th>CPU usage</th>
                        <th>Log filename</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?foreach($val['cronJobs'] as $toolJob){?>
                        <?/** @var $toolJob ToolJob*/?>
                        <?if ($toolJob->group !== $group) {?>
                            <tr>
                                <td colspan="10">
                                    <b><a href="#<?=$id = preg_replace('~\W+~sui', '-', $toolJob->group)?>" id="<?=$id ?>"><?=$toolJob->group?></a></b>
                                </td>
                            </tr>
                            <?$group = $toolJob->group;?>
                        <?}?>
                        <tr>
                            <td style="font-family: Menlo, Monaco, Consolas, monospace">
                                <?=preg_replace('~(--(?:tool|queue-name|event-processor)=)(\S*)~', '$1<span style="color: blue">$2</span>', preg_replace('~2>&1.*~', '', $toolJob->command))?>
                            </td>
                            <td style="white-space: nowrap;" class="cpu-usage __project-<?=$toolJob->project->project_name?> __key-<?=$toolJob->key?>">
                                <?if (isset($cpuUsages[$toolJob->key][$toolJob->project->project_name])) {?>
                                    <?/* @var $cpuUsage CpuUsage */?>
                                    <?$cpuUsage = $cpuUsages[$toolJob->key][$toolJob->project->project_name];?>
                                    Время: <time><?=date('Y-m-d H:i:s', strtotime($cpuUsage->last_run_time))?></time><br />
                                    Продолжительность: <span class="duration"><?=$cpuUsage->last_run_duration?> сек.</span><br />
                                    <img src="<?=$toolJob->getSmallTimeRealGraphSrc()?>" style="border: solid 1px #eee" /><br />
                                    Ошибка: <span class="error-mark"><?=$cpuUsage->last_exit_code
                                        ? TbHtml::labelTb('Да', ['color' => TbHtml::ALERT_COLOR_DANGER])
                                        : "Нет"
                                    ?></span><br />
                                <?} else {?>
                                    Время: <time>&ndash;</time><br />
                                    Продолжительность: <span class="duration">&ndash;</span><br />
                                    <img src="<?=$toolJob->getSmallTimeRealGraphSrc()?>" style="border: solid 1px #eee" /><br />
                                    Ошибка: <span class="error-mark">&ndash;</span><br />
                                <?}?>
                            </td>
                            <td style="white-space: nowrap">
                                <?if ($stopper = $toolJob->getToolJobStopped()) {?>
                                    Остановлено до <?=date('H:i', strtotime($stopper->stopped_till))?>
                                <?} else {?>
                                    Активен
                                <?}?>
                                <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_INFO_SIGN), $this->createUrl("/cronjobs/getInfo", [
                                    'key' => $toolJob->key,
                                    'project' => $val['project']->project_name
                                ]), "Запросить информацию о работающих процессах", [
                                    'class' => '__get-process-info',
                                    'style' => 'color: orange',
                                ])?>
                            </td>
                            <td class="cpu-usage __project-<?=$toolJob->project->project_name?> __key-<?=$toolJob->key?>">
                                <div class="cpu-usage-time">
                                    <?=isset($cpuUsages[$toolJob->key][$toolJob->project->project_name])
                                        ? sprintf('%.2f', $cpuUsages[$toolJob->key][$toolJob->project->project_name]->cpu_time / 1000)
                                        : 0
                                    ?>&nbsp;сек
                                </div>
                                <img src="<?=$toolJob->getSmallCpuUsageGraphSrc()?>" style="border: solid 1px #eee" />
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
                                        'style' => 'font-size: '.$size,
                                        'class' => '__kill-process',
                                    ])?>
                                    &nbsp;
                                    <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_REMOVE), $this->createUrl("/cronjobs/kill", [
                                        'key' => $toolJob->key,
                                        'signal' => 9,
                                        'project' => $val['project']->project_name
                                    ]), "Жестко убить работающие процессы (sudo kill -9)", [
                                        'class' => '__kill-process __hard-kill',
                                        'style' => 'font-size: '.$size."; color: red",
                                    ])?>
                                <?}?>
                            </td>
                        </tr>
                    <?}?>
                </tbody>
            </table>
        </div>
    <?}?>
</div>

<script type="text/javascript">
    $().ready(function(){
        $('.__get-process-info, .__kill-process').on('click', function(e){

            if ($(this).hasClass('__kill-process') && !confirm("Вы уверены что хотите завершить процессы?")) {
                return false;
            }

            if ($(this).hasClass('__hard-kill') && prompt("Введите строку \"подтверждаю\"") != "подтверждаю"){
                return false;
            }

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
            }).error(function(state, type, error){
                alert(error);
                that.innerHTML = html;
            });

            e.preventDefault();
        });


        webSocketSubscribe('updateToolJonPerformanceStats', function(event){
            var objs = $('.cpu-usage.__key-' + event.key + '.__project-' + event.project);
            $('.cpu-usage-time', objs).html(event.cpuTime.toFixed(2) + ' сек');
            objs.css({'color': 'red'});
            $('time', objs).html(event.last_run_time);
            $('span.duration', objs).html(event.last_run_duration + ' сек.');
            $('span.error-mark', objs).html(event.last_exit_code
                ? '<?=TbHtml::labelTb('Да', ['color' => TbHtml::ALERT_COLOR_DANGER])?>'
                : 'Нет'
            );
            setTimeout(function(){
                objs.css({'color': 'inherit'});
            }, 500);
        });
    });
</script>