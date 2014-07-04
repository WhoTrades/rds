<?php /* @var $this Controller */ ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />

    <link rel="stylesheet" type="text/css" href="/main/www/css/styles.css" />

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>

	<?php Yii::app()->bootstrap->register(); ?>
</head>

<body>

<?php
$this->widget('bootstrap.widgets.TbNavbar',array(
    'items'=>array(
        array(
            'class'=>'bootstrap.widgets.TbMenu',
            'items'=>array(
                array('label'=>'Home', 'url'=>array('/site/index')),
                array('label'=>'Projects', 'url'=>array('/project/admin'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Workers', 'url'=>array('/worker/admin'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Versions', 'url'=>array('/releaseVersion/admin'), 'visible'=>!Yii::app()->user->isGuest),
                //array('label'=>'Project Workers', 'url'=>array('/project2worker/admin'), 'visible'=>!Yii::app()->user->isGuest),
                array('label'=>'Logout ('.Yii::app()->user->name.')', 'url'=>array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest)
            ),
        ),
    ),
)); ?>
<br /><Br />

<div class="container" id="page">

	<?php if(isset($this->breadcrumbs)):?>
		<?php $this->widget('bootstrap.widgets.TbBreadcrumbs', array(
			'links'=>$this->breadcrumbs,
		)); ?><!-- breadcrumbs -->
	<?php endif?>

	<?php echo $content; ?>

	<div class="clear"></div>

</div><!-- page -->
<script>
    w= 10;
    h=20;
    b = document.getElementsByTagName('body')[0];
    b.style.cursor = 'none'

    d = document.createElement('div');
    d.id = 'nicecursor';
    b.appendChild(d);
    a = document.getElementById('nicecursor');
    a.style.position = 'absolute';
    a.style.top='10px';
    a.style.left='10px';
    a.style.width=w+'px';
    a.style.height=h+'px';
    a.style.backgroundColor='white';
    a.style.pointerEvents='none';

    b.onmousemove = function(e){
        a.style.left = e.pageX - e.pageX % w   +'px';
        a.style.top = e.pageY - e.pageY % h +'px';

    }

</script>
</body>
</html>
