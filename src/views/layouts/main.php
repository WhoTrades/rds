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
    array('label'=>'Проекты', 'url'=>array('/project/admin'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'project'),
    array('label'=>'Сборщики', 'url'=>array('/worker/admin'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'worker'),
    array('label'=>'Версии', 'url'=>array('/releaseVersion/admin'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'releaseVersion'),
    array('label'=>'Журнал', 'url'=>array('/log/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'log'),
    array('label'=>'JIRA', 'url'=>array('/jira/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'jira'),
    array('label'=>'Обслуживание', 'url'=>array('/maintenanceTool/index'), 'visible'=>!Yii::app()->user->isGuest, 'active' => $this->getId() == 'maintenanceTool'),
    array('label'=>'Выйти ('.Yii::app()->user->name.')', 'url'=>array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest)
)); ?>

<?$this->widget('PostMigration', [])?>


<div id="page" class="container-fluid">
	<?php echo $content; ?>
	<div class="clear"></div>
</div><!-- page -->

</body>
</html>
