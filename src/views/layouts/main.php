<?php
use app\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;
use yii\bootstrap\Alert;
use yii\helpers\ArrayHelper;

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
$controllerId       = \Yii::$app->controller->id;
$controllerUniqueId = \Yii::$app->controller->uniqueId;
$actionId           = \Yii::$app->controller->action->id;
$modulesNav         = [];

foreach (\Yii::$app->modules as $module) {
    if (!$module instanceof app\IHaveNavInterface) {
        continue;
    }

    $modulesNav = ArrayHelper::merge($modulesNav, $module::getNav($controllerId, $actionId));
}
NavBar::begin(['brandLabel' => 'RDS']);
echo Nav::widget(
    [
        'options' => ['class' => 'navbar-nav navbar-left'],
        'activateParents' => true,
        'items' => ArrayHelper::merge(
            [
                'home' => [
                    'label' => 'Главная',
                    'url' => ['/site/index'],
                    'active' => $controllerId == 'site',
                ],
                'releases' => [
                    'label' => 'Настройка сборки',
                    'url' => ['/project/admin'],
                    'visible' => !\Yii::$app->user->isGuest,
                    'items' => [
                        ['label' => 'Проекты', 'url' => ['/project/admin'], 'active' => $controllerId == 'project'],
                        ['label' => 'Сборщики', 'url' => ['/worker/admin'], 'active' => $controllerId == 'worker'],
                        ['label' => 'Версии', 'url' => ['/release-version/admin'], 'active' => $controllerId == 'release-version'],
                        ['label' => 'Ограничение функциональности', 'url' => ['/Whotrades/system/index'], 'active' => $controllerId == 'system'],
                    ],
                ],
            ],
            $modulesNav,
            [
                'journal' => [
                    'label' => 'Журнал',
                    'url' => ['/log/index'],
                    'visible' => !\Yii::$app->user->isGuest,
                ],
                'users' => [
                    'label' => 'Пользователи',
                    'url' => ['/user/admin'],
                    'active' => $controllerUniqueId == 'user/admin',
                    'visible' => Yii::$app->user->identity->isAdmin,
                ],
                'logOut' => [
                    'label' => \Yii::$app->user->getIsGuest() ? "" : \Yii::$app->user->getIdentity()->email,
                    'icon' => 'log-out',
                    'url' => ['/site/logout'],
                    'visible' => !\Yii::$app->user->isGuest,
                    'items' => [
                        ['label' => 'Профиль', 'url' => ['/user/settings/profile']],
                        ['label' => 'Выйти', 'url' => ['/site/logout']],
                    ],
                ],
            ]
        ),
    ]
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
