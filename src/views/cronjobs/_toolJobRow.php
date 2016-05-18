<?php
/** @var $toolJob ToolJob */
/** @var $Project Project */
?>
<tr id="<?=$toolJob->getLoggerTag()?>" class="active-tr project-<?=$toolJob->project->project_name?>">
    <td style="font-family: Menlo, Monaco, Consolas, monospace">
        <?=TbHtml::tooltip(TbHtml::icon(TbHtml::ICON_LINK, [
            'class' => 'active-link',
            'style' => 'cursor: pointer',
            'border' => 'solid 1px #eee',
            'padding' => '3px',
        ]), "#" . $toolJob->getLoggerTag(), 'Постоянная ссылка на процесс')?>
        <span class="command">
            <?=preg_replace(
                '~(--(?:tool|queue-name|event-processor)=)(\S*)~',
                '$1<span class="highlight" style="color: blue">$2</span>',
                preg_replace('~2>&1.*~', '', $toolJob->command)
            )?>
        </span>
        <hr style="margin: 5px 0px" />
        <?php $tag = str_replace('=', '\=', preg_replace('~.*logger -p \S+ -t (\S+).*~', '$1', $toolJob->command))?><br />
        Log: <input type="text"
                    value="/var/log/storelog/cronjobs/<?=$tag?>.log"
                    onclick="this.select()"
                    title="Имя файла лога на сервере nyr-wt-lg1.whotrades.net"
                    style="width: 60%" />
        <button style="width: 10%" class="__get-log-tail" tag="<?=$tag?>" rel="30">tail&nbsp;-30</button>
        <button style="width: 10%" class="__get-log-tail" tag="<?=$tag?>" rel="100">tail&nbsp;-100</button>
        <button style="width: 10%" onclick="window.open('/cronjobs/log/?tag=<?=$tag?>&lines=1000&plainText=1')">tail&nbsp;-1000</button><br /><br />
    </td>
    <td>
        <div style="white-space: nowrap">
            <?php if ($stopper = $toolJob->getToolJobStopped()) {?>
                Остановлено до <?=date('H:i', strtotime($stopper->stopped_till))?>
            <?php } else {?>
                Активен
            <?php }?>
            <?=TbHtml::tooltip(
                TbHtml::icon(TbHtml::ICON_INFO_SIGN),
                $this->createUrl("/cronjobs/getInfo", [
                    'key' => $toolJob->key,
                    'project' => $Project->project_name,
                ]),
                "Запросить информацию о работающих процессах",
                [
                    'class' => '__get-process-info',
                    'style' => 'color: orange',
                ]
            )?>
        </div>
        <?php if ($stopper) {?>
            <a href="<?=$this->createUrl("/cronjobs/start", [
                'key' => $toolJob->key,
                'projectId' => $Project->obj_id,
            ])?>" class="ajax-url">
                <?=TbHtml::icon(TbHtml::ICON_PLAY, ['style' => 'font-size: 2em; color: green',])?>
            </a><br />
        <?php } else {?>
            <div style="white-space: nowrap">
                <?php foreach (['0.7em' => '5 minutes', '1em' => '15 minutes', '1.3em' => '1 hour', '1.7em' => '3 hours', '2.0em' => '1 day'] as $size => $interval) {?>
                    <?=TbHtml::tooltip(
                        TbHtml::icon(TbHtml::ICON_STOP),
                        $this->createUrl("/cronjobs/stop", [
                            'key' => $toolJob->key,
                            'interval' => $interval,
                            'projectId' => $Project->obj_id,
                        ]),
                        "Не запускать " . $interval,
                        [
                            'style' => 'font-size: ' . $size,
                            'class' => 'ajax-url',
                        ]
                    )?>
                <?php }?>
            </div>
        <?php }?>
        <?=TbHtml::tooltip(
            TbHtml::icon(TbHtml::ICON_REMOVE_CIRCLE),
            $this->createUrl("/cronjobs/kill", [
                'key' => $toolJob->key,
                'project' => $Project->project_name,
            ]),
            "Мягко завершить работающие процессы (SIGTERM)",
            [
                'style' => 'font-size: 2em',
                'class' => '__kill-process',
            ]
        )?>
        &nbsp;
        <?=TbHtml::tooltip(
            TbHtml::icon(TbHtml::ICON_REMOVE),
            $this->createUrl("/cronjobs/kill", [
                'key' => $toolJob->key,
                'signal' => 9,
                'project' => $Project->project_name,
            ]),
            "Жестко убить работающие процессы (sudo kill -9)",
            [
                'class' => '__kill-process __hard-kill',
                'style' => 'font-size: 2em; color: red',
            ]
        )?>
    </td>
    <td style="white-space: nowrap;" class="cpu-usage __project-<?=$toolJob->project->project_name?> __key-<?=$toolJob->key?>">
        <?php if (isset($cpuUsages[$toolJob->key][$toolJob->project->project_name])) {?>
            <?php /* @var $cpuUsage CpuUsage */?>
            <?php $cpuUsage = $cpuUsages[$toolJob->key][$toolJob->project->project_name];?>
            Время: <time><?=date('Y-m-d H:i:s', strtotime($cpuUsage->last_run_time))?></time><br />
            Продолжительность: <span class="duration"><?=$cpuUsage->last_run_duration?> сек.</span><br />
            <a href="<?=$toolJob->getSmallTimeRealGraphSrc(false, 800, 600, false)?>" target="_blank" rel="tooltip" data-original-title="Открыть график в большем масштабе">
                <img
                    src="<?=$toolJob->getSmallTimeRealGraphSrc(true, $width = 180, $height = 60, true)?>"
                    style="width: <?=$width?>px; height: <?=$height?>px;border: solid 1px #eee"
                />
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
        <?php } else {?>
            Время: <time>&ndash;</time><br />
            Продолжительность: <span class="duration">&ndash;</span><br />
            <a href="<?=$toolJob->getSmallTimeRealGraphSrc(false, 800, 600, false)?>" target="_blank" rel="tooltip" data-original-title="Открыть график в большем масштабе">
                <img
                    src="<?=$toolJob->getSmallTimeRealGraphSrc(true, $width = 180, $height = 60, true)?>"
                    style="width: <?=$width?>px; height: <?=$height?>px;border: solid 1px #eee"
                />
            </a>
            <br />
            Ошибка: <span class="error-mark">&ndash;</span><br />
        <?php }?>
    </td>
    <td class="cpu-usage __project-<?=$toolJob->project->project_name?> __key-<?=$toolJob->key?>">
        <div class="cpu-usage-time">
            <?=isset($cpuUsages[$toolJob->key][$toolJob->project->project_name])
                ? sprintf('%.2f', $cpuUsages[$toolJob->key][$toolJob->project->project_name]->cpu_time / 1000)
                : 0
            ?>&nbsp;сек
        </div>
        <a href="<?=$toolJob->getSmallCpuUsageGraphSrc(false, 800, 600, false)?>" target="_blank" rel="tooltip" data-original-title="Открыть график в большем масштабе">
            <img
                src="<?=$toolJob->getSmallCpuUsageGraphSrc(true, $width = 100, $height = 60, true)?>"
                style="width: <?=$width?>px; height: <?=$height?>px;border: solid 1px #eee"
            />
        </a>
        <br />
    </td>
</tr>

