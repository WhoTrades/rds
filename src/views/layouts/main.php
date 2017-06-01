<?php
use app\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;
use yii\bootstrap\Alert;

/** @var $this yii\web\View */
/** @var $content string */
AppAsset::register($this);
$this->beginPage();
Yii::$app->webSockets->registerScripts($this);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="language" content="en" />

    <title>RDS: <?php echo Html::encode($this->title); ?></title>
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
    <script>
        document.onload = [];
        function webSocketSubscribe(channel, callback)
        {
            webSocketSession.subscribe(channel, function (topic, event) {
                callback(event.data.data);
            });
        }
    </script>
</head>

<body>
<?php
$this->beginBody();
$controllerId = \Yii::$app->controller->id;
NavBar::begin(['brandLabel' => 'RDS']);
echo Nav::widget(
    array(
        'options' => ['class' => 'navbar-nav navbar-left'],
        'items' => [
            array(
                'label' => 'Главная',
                'url' => array('/site/index'),
                'active' => $controllerId == 'site',
            ),
            array(
                'label' => 'Миграции',
                'url' => array('/hard-migration/index'),
                'visible' => !\Yii::$app->user->isGuest,
                'active' => $controllerId == 'hard-migration',
            ),
            array(
                'label' => 'РЕГИСТРАЦИЯ',
                'url' => 'mailto://anaumenko@corp.finam.ru?subject=RDS аккаунт&body=Я пробовал сам восстановить пароль,' .
                    ' но система не находит мою учетку. Прошу создать мне аккаунт в RDS на текущий ящик.',
                'visible' => \Yii::$app->user->isGuest,
            ),

            array(
                'label' => 'Настройка сборки',
                'url' => array('/project/admin'),
                'visible' => !\Yii::$app->user->isGuest,
                'active' => in_array($controllerId, ['project', 'worker', 'release-version']),
                'items' => [
                    array('label' => 'Проекты', 'url' => array('/project/admin'), 'active' => $controllerId == 'project'),
                    array('label' => 'Сборщики', 'url' => array('/worker/admin'), 'active' => $controllerId == 'worker'),
                    array('label' => 'Версии', 'url' => array('/release-version/admin'), 'active' => $controllerId == 'release-version'),
                ],
            ),
            array(
                'label' => 'Интеграция',
                'url' => array('/Wtflow/jira/index'),
                'visible' => !\Yii::$app->user->isGuest,
                'active' => in_array($controllerId, ['jira', 'developer', 'git']),
                'items' => [
                    ['label' => 'JIRA', 'url' => array('/Wtflow/jira/index'), 'active' => $controllerId == 'jira'],
                    ['label' => 'Разработчики', 'url' => array('/Wtflow/developer/index'), 'active' => $controllerId == 'developer'],
                    ['label' => 'Git', 'url' => array('/Wtflow/git/index'), 'active' => $controllerId == 'git' && $__action == 'index'],
                    ['label' => 'wtflow', 'url' => ['/Wtflow/git/wt-flow-stat', 'sort' => '-obj_created'], 'active' => $controllerId == 'git' && $__action == 'wt-flow-stat'],
                ],
            ),
            array(
                'label' => 'Обслуживание',
                'url' => array('/maintenance-tool/index'),
                'visible' => !\Yii::$app->user->isGuest,
                'active' => in_array($controllerId, ['maintenanceTool', 'alert', 'cronjobs', 'gitBuild']),
                'items' => [
                    //['label'=>'Управление ключевыми тулами', 'url'=>array('/maintenanceTool/index'), 'active' => $controllerId == 'maintenanceTool'],
                    ['label' => 'Сигнализация', 'url' => array('/alert/index'), 'active' => $controllerId == 'alert'],
                    ['label' => 'Фоновые задачи', 'url' => array('/cronjobs/index'), 'active' => $controllerId == 'cronjobs'],
                    ['label' => 'Пересборка веток', 'url' => array('/Wtflow/git-build'), 'active' => $controllerId == 'git-build'],
                    ['label' => 'Ограничение функциональности', 'url' => array('/system/index'), 'active' => $controllerId == 'system'],
                ],
            ),
            array(
                'label' => 'Журнал',
                'url' => array('/log/index'),
                'visible' => !\Yii::$app->user->isGuest,
                'active' => $controllerId == 'log',
            ),

            array(
                'label' => \Yii::$app->user->getIsGuest() ? "" : \Yii::$app->user->getIdentity()->email,
                'icon' => 'log-out',
                'url' => array('/site/logout'),
                'visible' => !\Yii::$app->user->isGuest,
                'items' => [
                    ['label' => 'Профиль', 'url' => array('/user/settings/profile')],
                    ['label' => 'Выйти', 'url' => array('/site/logout')],
                ],
            ),
        ],
    )
);
NavBar::end();
?>

<?=app\widgets\GlobalWarnings::widget([])?>
<?=app\widgets\PostMigration::widget([])?>


<div id="page" class="container-fluid">
    <?php echo $content; ?>
    <div class="clear"></div>
</div><!-- page -->
<?php $this->endBody() ?>
<script>
    document.onload.push(function() {
        $('body').on('click', '.ajax-url', function (e) {
            var that = this;
            var html = this.innerHTML;
            that.innerHTML = <?=json_encode(yii\bootstrap\BaseHtml::icon('refresh'))?>;
            $.ajax({url: this.href}).done(function () {
                that.innerHTML = html;
            });
            e.preventDefault();
        });
    });

    for (var i in document.onload) {
        if (!document.onload.hasOwnProperty(i)) {
            continue;
        }
        document.onload[i]();
    }

    webSocketSubscribe('deployment_status_changed', function(event){
        if (event.deployment_enabled) {
            var title = "Обновление серверов включено";
            var body = <?=json_encode(Alert::widget([
                'options' => ['class' => 'alert-success'],
                'body' => "Теперь можно собирать, активировать сборки, синхронизировать конфигурацию",
            ]))?>;
        } else {
            var title = "Обновление серверов отключено";
            var body = <?=json_encode(Alert::widget([
                'options' => ['class' => 'alert-danger'],
                'body' => "Сборки проектов, активация сборок и синронизация конфигов временно отключена",
            ]))?>;
            body += '<b>Причина</b>: ' + event.reason;
        }
        $("#modal-popup .modal-header h4").html(title);
        $("#modal-popup .modal-body").html(body);
        $("#modal-popup").modal("show");
    });

</script>
</body>
</html>
<?php
$this->endPage();
