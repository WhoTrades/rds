<?
$this->pageTitle = "Фоновые процессы";
?>
<h1>
    <?=$this->pageTitle?>
    <small><a href="<?=$this->createUrl("cpuUsageReport")?>">CPU usage report</a></small>
</h1>
<div style="clear: both"></div>

<ul class="nav nav-tabs" role="menu">
    <?foreach ($cronJobs as $val) {?>
        <li class="<?=$val['project']->project_name == $project ? 'active' : ''?>">
            <a tabindex="-1" href="?project=<?=$val['project']->project_name?>" aria-expanded="true"><?=$val['project']->project_name?></a>
        </li>
    <?}?>

    <li><?=TbHtml::textField('search', '', [
        'placeholder' => 'Быстрый фильтр',
        'class' => '__tools-filter',
    ])?></li>
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
                        <th>Действия</th>
                        <th style=" width: 120px;">Последний запуск</th>
                        <th>CPU usage</th>
                    </tr>
                </thead>
                <tbody>
                    <?foreach($val['cronJobs'] as $toolJob){?>
                        <?/** @var $toolJob ToolJob*/?>
                        <?if ($toolJob->group !== $group) {?>
                            <tr class="group-splitter active-tr" id="group-<?=$groupNormalized = preg_replace('~\W+~', '-', $toolJob->group)?>">
                                <td colspan="10">
                                    <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_LINK, [
                                        'class' => 'active-link',
                                        'style' => 'cursor: pointer',
                                        'border' => 'solid 1px #eee',
                                        'padding' => '3px',
                                    ]), "#group-$groupNormalized", 'Постоянная ссылка на процесс')?>

                                    <b><a href="#<?=$id = preg_replace('~\W+~sui', '-', $toolJob->group)?>" id="<?=$id ?>"><?=$toolJob->group?></a></b>
                                </td>
                            </tr>
                            <?$group = $toolJob->group;?>
                        <?}?>
                        <tr id="<?=$toolJob->getLoggerTag()?>" class="active-tr">
                            <td style="font-family: Menlo, Monaco, Consolas, monospace">
                                <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_LINK, [
                                    'class' => 'active-link',
                                    'style' => 'cursor: pointer',
                                    'border' => 'solid 1px #eee',
                                    'padding' => '3px',
                                ]), "#".$toolJob->getLoggerTag(), 'Постоянная ссылка на процесс')?>
                                <span class="command">
                                    <?=preg_replace('~(--(?:tool|queue-name|event-processor)=)(\S*)~', '$1<span class="highlight" style="color: blue">$2</span>', preg_replace('~2>&1.*~', '', $toolJob->command))?>
                                </span>
                                <hr style="margin: 5px 0px" />
                                <?$tag=str_replace('=', '\=', preg_replace('~.*logger -p \S+ -t (\S+).*~', '$1', $toolJob->command))?><br />
                                Log: <input type="text"
                                       value="/var/log/storelog/cronjobs/<?=$tag?>.log"
                                       onclick="this.select()"
                                       style="width: 60%" />
                                <button style="width: 10%" class="__get-log-tail" tag="<?=$tag?>" rel="30">tail&nbsp;-30</button>
                                <button style="width: 10%" class="__get-log-tail" tag="<?=$tag?>" rel="100">tail&nbsp;-100</button>
                                <button style="width: 10%" onclick="window.open('/cronjobs/log/?tag=<?=$tag?>&lines=1000&plainText=1')">tail&nbsp;-1000</button><br /><br />
                            </td>
                            <td>
                                <div style="white-space: nowrap">
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
                                </div>
                                <?if ($stopper) {?>
                                    <a href="<?=$this->createUrl("/cronjobs/start", [
                                        'key' => $toolJob->key,
                                        'projectId' => $val['project']->obj_id,
                                        'url' => $_SERVER['REQUEST_URI'],
                                    ])?>"><?=TbHtml::icon(TbHtml::ICON_PLAY, [
                                        'style' => 'font-size: 2em; color: green'
                                    ])?></a><br />
                                <?} else {?>
                                    <div style="white-space: nowrap">
                                        <?foreach (['0.7em' => '5 minutes', '1em' => '15 minutes', '1.3em' => '1 hour', '1.7em' => '3 hours', '2.0em' => '1 day'] as $size => $interval) {?>
                                            <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_STOP), $this->createUrl("/cronjobs/stop", [
                                                'key' => $toolJob->key,
                                                'interval' => $interval,
                                                'projectId' => $val['project']->obj_id,
                                                'url' => $_SERVER['REQUEST_URI'],
                                            ]), "Не запускать ".$interval, ['style' => 'font-size: '.$size])?>
                                        <?}?>
                                    </div>
                                <?}?>
                                <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_REMOVE_CIRCLE), $this->createUrl("/cronjobs/kill", [
                                    'key' => $toolJob->key,
                                    'project' => $val['project']->project_name
                                ]), "Мягко завершить работающие процессы (SIGTERM)", [
                                    'style' => 'font-size: 2em',
                                    'class' => '__kill-process',
                                ])?>
                                &nbsp;
                                <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_REMOVE), $this->createUrl("/cronjobs/kill", [
                                    'key' => $toolJob->key,
                                    'signal' => 9,
                                    'project' => $val['project']->project_name
                                ]), "Жестко убить работающие процессы (sudo kill -9)", [
                                    'class' => '__kill-process __hard-kill',
                                    'style' => 'font-size: 2em; color: red',
                                ])?>
                            </td>
                            <td style="white-space: nowrap;" class="cpu-usage __project-<?=$toolJob->project->project_name?> __key-<?=$toolJob->key?>">
                                <?if (isset($cpuUsages[$toolJob->key][$toolJob->project->project_name])) {?>
                                    <?/* @var $cpuUsage CpuUsage */?>
                                    <?$cpuUsage = $cpuUsages[$toolJob->key][$toolJob->project->project_name];?>
                                    Время: <time><?=date('Y-m-d H:i:s', strtotime($cpuUsage->last_run_time))?></time><br />
                                    Продолжительность: <span class="duration"><?=$cpuUsage->last_run_duration?> сек.</span><br />
                                    <a href="<?=$toolJob->getSmallTimeRealGraphSrc(false, 800, 600, false)?>" target="_blank" rel="tooltip" data-original-title="Открыть график в большем масштабе">
                                        <img src="<?=$toolJob->getSmallTimeRealGraphSrc(true, $width=180, $height=60, true)?>" style="width: <?=$width?>px; height: <?=$height?>px;border: solid 1px #eee" />
                                    </a>
                                    <br />
                                    Ошибка: <span class="error-mark"><?=$cpuUsage->last_exit_code
                                        ? TbHtml::labelTb('Да', [
                                                'color' => TbHtml::ALERT_COLOR_DANGER,
                                                'class' => "__get-log-tail",
                                                'tag' => $tag,
                                                'style' => 'cursor: pointer',
                                            ])
                                        : "Нет"
                                    ?></span><br />
                                <?} else {?>
                                    Время: <time>&ndash;</time><br />
                                    Продолжительность: <span class="duration">&ndash;</span><br />
                                    <a href="<?=$toolJob->getSmallTimeRealGraphSrc(false, 800, 600, false)?>" target="_blank" rel="tooltip" data-original-title="Открыть график в большем масштабе">
                                        <img src="<?=$toolJob->getSmallTimeRealGraphSrc(true, $width=180, $height=60, true)?>" style="width: <?=$width?>px; height: <?=$height?>px;border: solid 1px #eee" />
                                    </a>
                                    <br />
                                    Ошибка: <span class="error-mark">&ndash;</span><br />
                                <?}?>
                            </td>
                            <td class="cpu-usage __project-<?=$toolJob->project->project_name?> __key-<?=$toolJob->key?>">
                                <div class="cpu-usage-time">
                                    <?=isset($cpuUsages[$toolJob->key][$toolJob->project->project_name])
                                        ? sprintf('%.2f', $cpuUsages[$toolJob->key][$toolJob->project->project_name]->cpu_time / 1000)
                                        : 0
                                    ?>&nbsp;сек
                                </div>
                                <a href="<?=$toolJob->getSmallCpuUsageGraphSrc(false, 800, 600, false)?>" target="_blank" rel="tooltip" data-original-title="Открыть график в большем масштабе">
                                    <img src="<?=$toolJob->getSmallCpuUsageGraphSrc(true, $width=100, $height=60, true)?>" style="width: <?=$width?>px; height: <?=$height?>px;border: solid 1px #eee" />
                                </a>
                                <br />
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

        $('.__get-log-tail').on('click', function(e){
            this.disabled = true;
            var that = this;
            var td = $('td:first', $(this).parents('tr:first'));
            $('.alert', td).remove();

            $.ajax({
                url: '<?=$this->createUrl('log')?>',
                data: {
                    tag: $(this).attr('tag'),
                    lines: $(this).attr('rel')
                }
            }).done(function(text){
                that.disabled = false;
                $('.alert', td).remove();
                td.append(text);
                that.innerHTML = html;
            }).error(function(state, type, error){
                that.disabled = false;
                alert(error);
                that.innerHTML = html;
            });

            e.preventDefault();
        });

        $('.__tools-filter').keyup(function(e){
            if (this.value) {
                $('.group-splitter').hide();
            }

            var text = this.value.toLowerCase();

            $('.command').each(function(k, v){
                if ($(v).text().toLowerCase().indexOf(text) == -1) {
                    $(v).parents('tr:first').hide();
                } else {
                    $(v).parents('tr:first').show();
                }
            });
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


    if (document.location.toString().indexOf('#') != -1) {
        var id = a = document.location.toString().replace(/.*#(.*)/, '$1');
        $('#' + id).addClass('selected');
    }

    $('.active-link').click(function(){
        console.log(this);
        if ($(this).parents('tr:first').hasClass('selected')) {
            $(this).parents('tr:first').removeClass('selected');
        } else {
            $('.active-tr').removeClass('selected');
            $(this).parents('tr:first').addClass('selected');
        }
        document.location = document.location.toString().replace(/.*#(.*)/, '') + '#' + $(this).parents('tr:first').attr('id');
    });
</script>

<style>
    tr.selected {
        background-color: #ecf8be !important;
    }
</style>