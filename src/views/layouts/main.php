<?php /* @var $this Controller */ ?>
<?php /* @var $content string */ ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />

    <link rel="stylesheet" type="text/css" href="/css/styles.css" />

	<title>RDS: <?php echo CHtml::encode($this->pageTitle); ?></title>

	<?php Yii::app()->bootstrap->register(); ?>
    <?php Yii::app()->realplexor->registerScripts()?>
</head>

<body>

<?=TbHtml::tabs(array(
    array('label'=>'Главная', 'url'=>array('/site/index'), 'active' => $this->getId() == 'site'),
    array('label'=>'Миграции', 'url'=>array('/hardMigration/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'hardMigration'),

    array('label'=>'Настройка сборки', 'url'=>array('/project/admin'), 'visible'=>!Yii::app()->user->isGuest, 'active' => in_array($this->getId(), ['project', 'worker', 'releaseVersion']), 'items' => [
        array('label'=>'Проекты', 'url'=>array('/project/admin'), 'active' => $this->getId() == 'project'),
        array('label'=>'Сборщики', 'url'=>array('/worker/admin'), 'active' => $this->getId() == 'worker'),
        array('label'=>'Версии', 'url'=>array('/releaseVersion/admin'), 'active' => $this->getId() == 'releaseVersion'),
    ]),
    array('label'=>'Интеграция', 'url'=>array('/jira/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'jira' || $this->getId() == 'developer', 'items' => [
        ['label'=>'JIRA', 'url'=>array('/jira/index'), 'active' => $this->getId() == 'jira'],
        ['label'=>'Разработчики', 'url'=>array('/developer/index'), 'active' => $this->getId() == 'developer'],
    ],),
    array('label'=>'Обслуживание', 'url'=>array('/maintenanceTool/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'maintenanceTool', 'items' => [
        ['label'=>'Управление ключевыми тулами', 'url'=>array('/maintenanceTool/index'), 'active' => $this->getId() == 'maintenanceTool'],
        ['label'=>'Сигнализация', 'url'=>array('/alert/index'), 'active' => $this->getId() == 'alert'],
    ]),
    array('label'=>'Журнал', 'url'=>array('/log/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'log'),
    array('label'=>'Выйти ('.Yii::app()->user->name.')', 'url'=>array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest)
)); ?>

<?$this->widget('PostMigration', [])?>


<div id="page" class="container-fluid">
	<?php echo $content; ?>
	<div class="clear"></div>
</div><!-- page -->

</body>
</html>
