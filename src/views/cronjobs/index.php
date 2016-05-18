<?php
/** @var $this CronJobsController - название текущего проекта */
/** @var $Project Project - название текущего проекта */
/** @var $projects Project[] - Список всех проектов с кронами */
/** @var $cronJobs ToolJob[] - Список кронов */
/** @var $cpuUsageLastTruncate date - Дата последнего сброса статистики по использованию CPU */
$this->pageTitle = "Фоновые процессы";
?>
<h1>
    <?=$this->pageTitle?>
    <small><a href="<?=$this->createUrl("cpuUsageReport")?>">CPU usage report</a></small>
</h1>
<div style="clear: both"></div>

<ul class="nav nav-tabs" role="menu">
    <?php foreach ($projects as $val) {?>
        <li class="<?=$val->obj_id == $Project->obj_id ? 'active' : ''?>">
            <a tabindex="-1" href="?project=<?=$val->project_name?>" aria-expanded="true"><?=$val->project_name?></a>
        </li>
    <?php }?>

    <li><?=TbHtml::textField('search', '', [
        'placeholder' => 'Быстрый фильтр',
        'class' => '__tools-filter',
    ])?></li>
    <li style="float: right">
        <?=TbHtml::button(
            "Обнулить показатели CPU<br /><small>Последнее обнуление: <span>" .
                ($cpuUsageLastTruncate ? date('d.m.Y H:i', strtotime($cpuUsageLastTruncate)) : "никогда") .
                "</span></small>",
            [
                'color' => TbHtml::BUTTON_COLOR_DANGER,
                'onclick' => 'js:if (confirm("Вы уверены что хотите обнулить все показатели по использованию CPU")) {
                    var html = this.innerHTML;
                    this.innerHTML = ' . json_encode(TbHtml::icon(TbHtml::ICON_REFRESH)) . ';
                    this.disabled = true;
                    var obj = this;
                    $.ajax({url: "' . $this->createUrl('truncateCpuUsage') . '"}).done(function(){
                        obj.innerHTML = html;
                        obj.disabled = false;
                        location += "";
                    });
                }',
            ]
        )?>
    </li>
</ul>

<div class="tab-content">
    <div id="tab-<?=$Project->project_name?>" class="tab-pane fade active in table-responsive">
        <table class="items table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Команда</th>
                    <th style="width: 200px;">Действия</th>
                    <th style=" width: 120px;">Последний запуск</th>
                    <th>CPU usage</th>
                </tr>
            </thead>
            <tbody>
                <?php $group = false;?>
                <?php foreach ($cronJobs as $toolJob) {?>
                    <?php /** @var $toolJob ToolJob*/?>
                    <?php if ($toolJob->group !== $group) {?>
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
                        <?php $group = $toolJob->group;?>
                    <?php }?>
                    <?=$this->renderToolJobRow($toolJob, $cpuUsages);?>
                <?php }?>
            </tbody>
        </table>
</div>

<script type="text/javascript">
    $().ready(function(){
        $(document).on('click', '.__get-process-info, .__kill-process', function(e){

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

        $(document).on('click', '.__get-log-tail', function(e){
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
            } else {
                $('.group-splitter').show();
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

        webSocketSubscribe('updateToolJobPerformanceStats', function(event){
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

        webSocketSubscribe('updateToolJobRow-<?=$Project->project_name?>', function(event){
            console.log(event);
            var id = event.id;
            var projectName = event.projectName;
            console.log(id, projectName);
            var str = '#' + id + '.project-' + projectName;
            console.log('str', str);
            var obj = $(str);
            obj.html($(event.html).html());
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

<style type="text/css">
    tr.selected {
        background-color: #ecf8be !important;
    }
</style>
