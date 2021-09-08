<?php
use whotrades\rds\assets\AppAsset;
use whotrades\rds\assets\TranslationAsset;
use yii\helpers\Html;
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;
use yii\helpers\ArrayHelper;

/** @var $this yii\web\View */
/** @var $content string */
AppAsset::register($this);
TranslationAsset::register($this);
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
    </script>
    <script>
        <?php
        /** @var TranslationAsset $translationBundle */
        $translationBundle = $this->getAssetManager()->getBundle(TranslationAsset::class);
        $language = Yii::$app->language;
        $locale = explode('-', $language, 2)[0];
        ?>
        function t() {
            if (!window.i18next || !window.i18next.t || 'function' !== typeof window.i18next.t) {
                return arguments[0];
            }
            return window.i18next.t.apply(window.i18next, arguments);
        }
        document.onload.push(function () {
            window.i18next.use(window.i18nextICU).use(window.i18nextHttpBackend).init({
                i18nFormat: {
                    localeData: window.ReactIntlLocaleData['<?=$locale?>']
                },
                lng: '<?=$language?>',
                fallbackLng: 'en-US',
                load: 'currentOnly',
                debug: false,
                ns: ['<?=implode("','", $translationBundle->getTranslationNamespaces())?>'],
                backend: {
                    loadPath: '<?=Yii::getAlias('@web/translations')?>/{{lng}}/{{ns}}.json'
                }
            });
        });
    </script>
    <script>
        function webSocketSubscribe(channels, callback)
        {
            if (typeof channels === 'string') {
                channels = [channels];
            }

            for (var i = 0; i < channels.length; i++) {
                webSocketSession.subscribe(channels[i], function (topic, event) {
                    callback(event.data.data);
                });
            }
        }
    </script>
</head>

<body>
<?php
$this->beginBody();
$controllerId       = Yii::$app->controller->id;
$controllerUniqueId = Yii::$app->controller->uniqueId;
$actionId           = Yii::$app->controller->action->id;
$modulesNav         = [];

foreach (Yii::$app->modules as $module) {
    if (!$module instanceof whotrades\rds\IHaveNavInterface) {
        continue;
    }

    $modulesNav = ArrayHelper::merge($modulesNav, $module::getNav($controllerId, $actionId));
}
NavBar::begin([
    'brandLabel' => 'RDS',
    'innerContainerOptions' => ['class' => 'container-fluid'],
]);

echo Nav::widget(
    [
        'options' => ['class' => 'navbar-nav navbar-left'],
        'activateParents' => true,
        'items' => ArrayHelper::merge(
            [
                'home' => [
                    'label' => Yii::t('rds', 'menu_index'),
                    'url' => ['/site/index'],
                    'active' => $controllerId == 'site',
                ],
                'releases' => [
                    'label' => Yii::t('rds', 'menu_build_settings'),
                    'url' => ['/project/admin'],
                    'visible' => Yii::$app->user->can('developer'),
                    'items' => [
                        ['label' => Yii::t('rds', 'menu_projects'), 'url' => ['/project/admin'], 'active' => $controllerId == 'project'],
                        'workers' => ['label' => Yii::t('rds', 'menu_build_agents'), 'url' => ['/worker/admin'], 'active' => $controllerId == 'worker'],
                        ['label' => Yii::t('rds', 'menu_versions'), 'url' => ['/release-version/admin'], 'active' => $controllerId == 'release-version'],
                        ['label' => Yii::t('rds', 'menu_system'), 'url' => ['/system'], 'active' => $controllerId == 'system'],
                    ],
                ],
                'migrations' => [
                    'label' => Yii::t('rds', 'menu_migrations'),
                    'url' => ['/migration/index'],
                    'visible' => Yii::$app->user->can('developer'),
                    'items' => [
                        ['label' => Yii::t('rds', 'menu_pre_post_migrations'), 'url' => ['/migration/index'], 'active' => $controllerId == 'migration'],
                    ],
                ]
            ],
            $modulesNav,
            [
                'journal' => [
                    'label' => Yii::t('rds', 'menu_activity_log'),
                    'url' => ['/log/index'],
                    'visible' => Yii::$app->user->can('developer'),
                ],
                'users' => [
                    'label' => Yii::t('rds', 'menu_users'),
                    'url' => ['/user/admin'],
                    'active' => $controllerUniqueId == 'user/admin',
                    'visible' => Yii::$app->user->identity && Yii::$app->user->identity->isAdmin,
                ],
                'logOut' => [
                    'label' => Yii::$app->user->getIsGuest() ? "" : Yii::$app->user->getIdentity()->email,
                    'icon' => 'log-out',
                    'url' => ['/site/logout'],
                    'visible' => !Yii::$app->user->isGuest,
                    'items' => [
                        ['label' => Yii::t('rds', 'menu_user_profile'), 'url' => ['/user/settings/profile']],
                        ['label' => Yii::t('rds', 'menu_exit'), 'url' => ['/site/logout']],
                    ],
                ],
            ]
        ),
    ]
);
NavBar::end();
?>

<?=whotrades\rds\widgets\GlobalWarnings::widget([])?>
<?=whotrades\rds\widgets\PostMigration::widget([])?>


<div id="page" class="container-fluid">

    <?php
        yii\bootstrap\Modal::begin([
            'id' => 'release-request-use-form-modal',
            'header' => 'Активировать',
        ])->end();
        yii\bootstrap\Modal::begin([
            'id'            => 'modal-popup',
            'header'        => '<h4 class="modal-title"></h4>',
            'headerOptions' => ['class' => 'alert'],
        ])->end();

        echo $content;
    ?>

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

    webSocketSubscribe(['popup_message', 'popup_message_' + <?= Yii::$app->user->getId() ?>], function(event) {
        var popup = $('#modal-popup'),
            body  = event.body,
            title = event.title,
            type  = event.type || 'default';

        popup.find('.modal-body').html(body);
        popup.find('.modal-title').html(title);

        // Add message type to modal
        popup.find('.modal-header').removeClass(function () {
            return (this.className.match(/alert-\w+/i) || '').toString();
        });
        popup.find('.modal-header').addClass('alert-' + type);

        popup.modal('show');
    });

</script>
</body>
</html>
<?php
$this->endPage();
