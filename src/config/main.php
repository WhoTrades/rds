<?php
/**
 * Глобальный конфиг
 * Что бы его переопределить для себя используйте protected/config/config.local.php
 */

use whotrades\RdsSystem\lib\WebErrorHandler;

define('KEEP_STDOUT_UNCHANGED', true);

$config = array(
    'id' => 'RDS',
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'vendorPath' => __DIR__ . '/../../vendor',
    'runtimePath' => '/tmp/rds',
    'name' => 'Система управления релизами',

    'language' => 'ru-RU',
    'controllerNamespace' => 'whotrades\rds\controllers',

    'bootstrap' => array('log', 'debug', 'webSockets', 'dektrium\user\Bootstrap'),
    'layout' => 'column1',
    'modules' => array(
        // uncomment the following to enable the Gii tool
        'gii' => array(
            'class' => 'yii\gii\Module',
            'allowedIPs' => array('192.168.*', '10.0.2.2'),
        ),
        'debug' => [
            'class' => yii\debug\Module::class,
            'allowedIPs' => ['10.0.2.2', '::1'],
        ],
        'gridview' =>  [
            'class' => \kartik\grid\Module::class,
        ],
        'user' => [
            'class' => dektrium\user\Module::class,
            'enableUnconfirmedLogin' => true,
            'enableRegistration' => false,
            'enablePasswordRecovery' => true,
            'enableFlashMessages' => true,
            'admins' => [
                'entsupml@gmail.com',
            ],
            'modelMap' => [
                'User' => [
                    'class' => whotrades\rds\models\User\User::class,
                ],
                'Profile' => [
                    'class' => whotrades\rds\models\User\Profile::class,
                ],
                'Token' => [
                    'class' => whotrades\rds\models\User\Token::class,
                ],
                'Account' => [
                    'class' => whotrades\rds\models\User\Account::class,
                ],
            ],
            'mailer' => [
                'sender'                => 'rds@whotrades.org', // or ['no-reply@myhost.com' => 'Sender name']
                'welcomeSubject'        => '[RDS] Добро пожаловать',
                'confirmationSubject'   => '[RDS] Подтверждение регистрации',
                'reconfirmationSubject' => '[RDS] Смена email',
                'recoverySubject'       => '[RDS] Восстановление пароля',
            ],
        ],
    ),

    // application components
    'components' => array(
        'view' => [
            'class' => 'whotrades\rds\components\View',
        ],
        'request' => [
            'cookieValidationKey' => '873gl09glkdgtoGL',
        ],
        'commandInstanceMutex' => [
            'class' => 'yii\mutex\FileMutex',
            'mutexPath' => '/var/lib/cronjob/service-rds',
        ],
        'sentry' => [
            'class' => mito\sentry\Component::class,
            'dsn' => '<your sentry DSN>', // private DSN
        ],
        'diffStat' => array(
            'class' => whotrades\rds\components\DiffStat::class,
        ),
        'sessionCache' => [
            'class' => yii\caching\MemCache::class,
            'useMemcached' => true,
            'servers' => [
                [
                    'host' => 'localhost',
                    'port' => 11211,
                ],
            ],
        ],
        'user' => [
            'identityClass' => whotrades\rds\models\User\User::class,
            'loginUrl' => ['/user/login'],
        ],
        'log' => [
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => yii\log\SyslogTarget::class,
                    'identity' => 'yii-service-rds',
                    'levels' => ['info', 'warning', 'error'],
                    'except' => ['yii\db\Command::query'],
                    'facility' => LOG_LOCAL4,
                    'options' => 0,
                ],
                [
                    'class' => 'codemix\streamlog\Target',
                    'url' => 'php://stdout',
                    'levels' => ['info', 'warning', 'error'],
                    'except' => ['yii\db\Command::query'],
                    'logVars' => [],
                    'exportInterval' => 1,
                ],
            ],
        ],
        'session' => [
            'cache' => 'sessionCache',
            'class' => whotrades\rds\components\Session::class,
            'timeout' => 1440 * 30,
            'cookieParams' => [
                'httponly' => true,
                'lifetime' => 86400 * 30,
            ],
        ],
        'db' => array(
            'class' => yii\db\Connection::class,
            'dsn' => "pgsql:host=localhost;port=5432;dbname=rds",
            'username' => 'rds',
            'password' => 'rds',
            'charset' => 'utf8',
            'attributes' => [
                // an: Отключаем prepared statements, так как pgbouncer не умеет с ними работать
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
            'on afterOpen' => function ($event) {
                $event->sender->createCommand("SET TIME ZONE 'UTC'")->execute();
            },
        ),
        'EmailNotifier' => array(
            'class' => whotrades\rds\components\NotifierEmail::class,
            'releaseRequestedEmail' => '',
            'releaseRejectedEmail'  => '',
            'releaseReleasedEmail'  => '',
            'mergeConflictEmail'    => '',
        ),
        'jsdifflib' => array(
            'class' => whotrades\rds\extensions\jsdifflib\components\JsDiffLib::class,
        ),
        'webSockets' => array(
            'class' => whotrades\rds\extensions\WebSockets\WebSockets::class,
            'zmqLocations' => [
                'tcp://localhost:5554',
            ],
            'server' => '/websockets',
            'retryDelay' => '1',
            'maxRetries' => '999',
        ),
        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'baseUrl' => '/',
            'showScriptName' => false,
            'enablePrettyUrl' => true,
            'rules' => array(
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
            'hostInfo' => 'https://' . $_SERVER['HTTP_HOST'],
        ),
        'assetManager' => [
            'basePath' => __DIR__ . '/../../web/assets',
        ],
        'errorHandler' => array(
            'class' => WebErrorHandler::class,
            'discardExistingOutput' => false,
        ),
    ),

    'params' => array(
        'notify' => array(
            'releaseEngineers' => array(
                'phones' => '',
            ),
            'releaseRequest' => array(
                'phones' => '',
            ),
            'releaseReject' => array(
                'phones' => '',
            ),
            'status' => array(
                'phones' => '',
            ),
            'use' => array(
                'phones' => '',
            ),
        ),
    ),
);


return $config;
