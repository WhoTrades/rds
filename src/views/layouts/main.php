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
    <? Yii::app()->realplexor->registerScripts()?>
</head>

<body>

<?php
$this->widget('bootstrap.widgets.TbNavbar',array(
    'fixed' => false,
    'items'=>array(
        array(
            'class'=>'bootstrap.widgets.TbMenu',
            'items'=>array(
                array('label'=>'Главная', 'url'=>array('/site/index')),
                array('label'=>'Миграции', 'url'=>array('/hardMigration/index'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Проекты', 'url'=>array('/project/admin'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Сборщики', 'url'=>array('/worker/admin'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Версии', 'url'=>array('/releaseVersion/admin'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Журнал', 'url'=>array('/log/index'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'JIRA', 'url'=>array('/jira'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Выйти ('.Yii::app()->user->name.')', 'url'=>array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest)
            ),
        ),
    ),
)); ?>

<?$this->widget('PostMigration', [])?>


<div id="page" class="container-fluid">

	<?php if(isset($this->breadcrumbs)):?>
		<?php $this->widget('bootstrap.widgets.TbBreadcrumbs', array(
			'links'=>$this->breadcrumbs,
		)); ?><!-- breadcrumbs -->
	<?php endif?>

	<?php echo $content; ?>

	<div class="clear"></div>

</div><!-- page -->

</body>
</html>
