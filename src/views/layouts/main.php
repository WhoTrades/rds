<?php /* @var $this Controller */ ?>
<?php /* @var $content string */ ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />

    <link rel="stylesheet" type="text/css" media="screen,handled" href="/css/styles.css" />

	<title>RDS: <?php echo CHtml::encode($this->pageTitle); ?></title>

	<?php Yii::app()->bootstrap->register(); ?>
    <?php Yii::app()->webSockets->registerScripts()?>
    <script>
        function webSocketSubscribe(channel, callback)
        {
            webSocketSession.subscribe(channel, function (topic, event) {
                callback(event.data);
            });
        }
    </script>
</head>

<body>

<?$this->widget('yiistrap.widgets.TbNavbar', array(
        'display' => TbHtml::NAVBAR_DISPLAY_STATICTOP,
        'brandLabel' => '',
        'collapse' => true,
        'items' => [[
            'class' => 'yiistrap.widgets.TbNav',
            'items'=> [
                array('label'=>'Главная', 'url'=>array('/site/index'), 'active' => $this->getId() == 'site'),
                array('label'=>'Миграции', 'url'=>array('/hardMigration/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'hardMigration'),

                array('label'=>'Настройка сборки', 'url'=>array('/project/admin'), 'visible'=>!Yii::app()->user->isGuest, 'active' => in_array($this->getId(), ['project', 'worker', 'releaseVersion']), 'items' => [
                    array('label'=>'Проекты', 'url'=>array('/project/admin'), 'active' => $this->getId() == 'project'),
                    array('label'=>'Сборщики', 'url'=>array('/worker/admin'), 'active' => $this->getId() == 'worker'),
                    array('label'=>'Версии', 'url'=>array('/releaseVersion/admin'), 'active' => $this->getId() == 'releaseVersion'),
                ]),
                array('label'=>'Интеграция', 'url'=>array('/Wtflow/jira/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => in_array($this->getId(), ['jira', 'developer', 'git']), 'items' => [
                    ['label'=>'JIRA', 'url'=>array('/Wtflow/jira/index'), 'active' => $this->getId() == 'jira'],
                    ['label'=>'Разработчики', 'url'=>array('/Wtflow/developer/index'), 'active' => $this->getId() == 'developer'],
                    ['label'=>'Git', 'url'=>array('/Wtflow/git/index'), 'active' => $this->getId() == 'git' && $this->action->id == 'index'],
                    ['label'=>'wtflow', 'url'=>array('/Wtflow/git/wtflowStat'), 'active' => $this->getId() == 'git' && $this->action->id == 'wtflowStat'],
                ],),
                array(
                    'label'=>'Обслуживание',
                    'url'=>array('/maintenanceTool/index'),
                    'visible'=>!Yii::app()->user->isGuest,
                    'active' => in_array($this->getId(), ['maintenanceTool', 'alert', 'cronjobs', 'gitBuild']),
                    'items' => [
                        //['label'=>'Управление ключевыми тулами', 'url'=>array('/maintenanceTool/index'), 'active' => $this->getId() == 'maintenanceTool'],
                        ['label'=>'Сигнализация', 'url'=>array('/alert/index'), 'active' => $this->getId() == 'alert'],
                        ['label'=>'Фоновые задачи', 'url'=>array('/cronjobs/index'), 'active' => $this->getId() == 'cronjobs'],
                        ['label'=>'Пересборка веток', 'url'=>array('/Wtflow/gitBuild'), 'active' => $this->getId() == 'gitBuild'],
                    ]
                ),
                array('label'=>'Журнал', 'url'=>array('/log/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'log'),
                array('icon'=>Tbhtml::ICON_LOG_OUT, 'label' => Yii::app()->user->name, 'url'=>array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest)
            ],
        ]],
    )
); ?>

<?$this->widget('PostMigration', [])?>


<div id="page" class="container-fluid">
	<?php echo $content; ?>
	<div class="clear"></div>
</div><!-- page -->
<script>
    $('body').on('click', '.ajax-url', function(e){
        var that = this;
        var html = this.innerHTML;
        that.innerHTML = <?=json_encode(TbHtml::icon(TbHtml::ICON_REFRESH))?>;
        $.ajax({url: this.href}).done(function(){
            that.innerHTML = html;
        });
        e.preventDefault();
    });
</script>
</body>
</html>
