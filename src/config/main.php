<?php

/**
 * Глобальный конфиг
 * Что бы его переопределить для себя используйте protected/config/config.local.php
 */

use kartik\grid\Module;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use whotrades\rds\extensions\PsrTarget\PsrTarget;
use whotrades\rds\models\User\User;
use whotrades\rds\services\DeployService;
use whotrades\rds\services\DeployServiceInterface;
use whotrades\rds\services\strategies\CronConfigNoProcessingStrategy;
use whotrades\rds\services\strategies\CronConfigProcessingStrategyInterface;
use whotrades\RdsSystem\lib\WebErrorHandler;
use whotrades\rds\services\MigrationService;
use \whotrades\rds\models\Worker;
use \whotrades\rds\models\ReleaseRequest;
use yii\base\Application;
use \yii\helpers\Url;
use tuyakhov\notifications\Notifier;
use tuyakhov\notifications\channels\MailChannel;
use whotrades\rds\events\NotificationEventHandler;
use whotrades\rds\services\NotificationService;
use whotrades\rds\services\NotificationServiceInterface;
use yii\i18n\PhpMessageSource;
use whotrades\RdsSystem\Migration\LogAggregatorUrlInterface as MigrationLogAggregatorUrlInterface;
use whotrades\rds\components\MigrationLogAggregatorUrl;

$config = array(
    'id' => 'RDS',
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'vendorPath' => __DIR__ . '/../../vendor',
    'runtimePath' => '/tmp/rds',
    'name' => 'Система управления релизами',

    'language' => 'en-US',
    'controllerNamespace' => 'whotrades\rds\controllers',

    'bootstrap' => array('log', 'webSockets', 'dektrium\user\Bootstrap'),
    'layout' => 'column1',
    'modules' => array(
        // uncomment the following to enable the Gii tool
        'gii' => array(
            'class' => 'yii\gii\Module',
            'allowedIPs' => array('192.168.*', '10.0.2.2'),
        ),
        'gridview' =>  [
            'class' => Module::class,
        ],
        'user' => [
            'class' => dektrium\user\Module::class,
            'enableUnconfirmedLogin' => true,
            'enableRegistration' => false,
            'enablePasswordRecovery' => true,
            'enableFlashMessages' => true,
            'admins' => ['rds'],
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
        'rbac' => dektrium\rbac\RbacWebModule::class,
    ),

    // application components
    'components' => array(
        'view' => [
            'class' => 'whotrades\rds\components\View',
            'theme' => [
                'pathMap' => [
                    '@dektrium/user/views' => '@app/views/dektrium-user',
                ],
            ],
        ],
        'request' => [
            'cookieValidationKey' => '873gl09glkdgtoGL',
        ],
        'commandInstanceMutex' => [
            'class' => 'yii\mutex\FileMutex',
            'mutexPath' => '/tmp/rds/mutex',
            'fileMode' => 0777,
            'dirMode' => 0777,
        ],
        'diffStat' => array(
            'class' => whotrades\rds\components\DiffStat::class,
        ),
        // ag: TODO Remove after WTA-1977
        'smsSender' => [
            'class' => whotrades\rds\components\Sms\Sender::class,
        ],
        'sessionCache' => [
            'class' => yii\caching\DbCache::class,
            'cacheTable' => 'rds.session',
        ],
        'user' => [
            'class' => yii\web\User::class,
            'identityClass' => whotrades\rds\models\User\User::class,
            'loginUrl' => '/user/login',
        ],
        'log' => [
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => PsrTarget::class,
                    'logVars' => [],
                    'exportInterval' => 1,
                    'except' => ['yii\db\Command::query'],
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
        // ag: TODO Remove after WTA-1977
        'EmailNotifier' => array(
            'class' => whotrades\rds\components\NotifierEmail::class,
            'releaseRequestedEmail' => 'noreply@example.com',
            'releaseRejectedEmail'  => 'noreply@example.com',
            'releaseReleasedEmail'  => 'noreply@example.com',
            'mergeConflictEmail'    => 'noreply@example.com',
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
            'cache' => null,
        ),
        'assetManager' => [
            'basePath' => __DIR__ . '/../../web/assets',
        ],
        'errorHandler' => array(
            'class' => WebErrorHandler::class,
            'discardExistingOutput' => false,
        ),
        'authManager' => [
            'class' => dektrium\rbac\components\DbManager::class,
            'itemTable' => 'rds.user_rbac_item',
            'itemChildTable' => 'rds.user_rbac_item_child',
            'assignmentTable' => 'rds.user_rbac_assignment',
            'ruleTable' => 'rds.user_rbac_rule',
        ],
        'migrationService' => [
            'class' => MigrationService::class,
        ],
        'i18n' => [
            'translations' => [
                'rds*' => [
                    'class' => PhpMessageSource::class,
                    'basePath' => '@app/translations',
                    'sourceLanguage' => 'en-US',
                    'forceTranslation' => true, // We use placeholders instead of real messages, so we should force translation into real en-US messages.
                    'fileMap' => [
                        'rds' => 'rds.php',
                        'rds/errors' => 'errors.php',
                    ],
                ],
            ],
        ],
    ),

    'params' => array(
        'migrationAutoApplicationEnabled' => true,
        'autoReleaseRequestUserId' => 1,
        'projectMigrationUrlMask' => [
                '*' => function ($migration, $projectName, $type, $branch) {
                    /** @var string $migration - migration name with url slashes */
                    return "https://github.com/WhoTrades/rds/blob/master/src/migrations/$migration.php?at=refs/heads/$branch";
                }
        ],
        'projectMigrationBitBucketBranch' => 'master',
        'messaging' => [
            'host'  => 'localhost',
            'port'  => 5672,
            'user'  => 'rds',
            'pass'  => 'rds',
            'vhost' => '/',
        ],
        'garbageCollector' => [
            'minTimeAtProd' => '1 week',
            'minBuildsCountBeforeActive' => 15,
            'exampleProjectName' => [
                'minTimeAtProd' => '0 week',
                'minBuildsCountBeforeActive' => 7,
            ],
        ],
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
        'logger' => [
            'processors' => [
                new PsrLogMessageProcessor(),
            ],
            'handlers' => [
                new StreamHandler("php://stdout", Logger::INFO),
            ],
        ],
        'sentry' => [
            'baseUrl' => 'https://sentry.com/sentry/',
            'projectNameMap' => [], // ag: Mapping of RDS project name to Sentry project name
        ],
        'workerUrlGenerator' => function (Worker $worker) {
            return Url::to(['/worker/admin']);
        },
        'releaseRequestCommentGenerator' => function (ReleaseRequest $releaseRequest) {
            return strip_tags($releaseRequest->rr_comment) . "<br />";
        },
        'buildVersionMetricsGenerator' => function (ReleaseRequest $releaseRequest) {
            if (!$releaseRequest->rr_built_time) {
                return $releaseRequest->rr_build_version;
            }
            $metrics = $releaseRequest->getBuildMetrics();

            $metricsHtml = $releaseRequest->rr_build_version . "<br />Build: <b>{$metrics['time_build']}</b>s. ";
            if (!empty($metrics['time_activation'])) {
                $metricsHtml .= "Activation: <b>{$metrics['time_activation']}</b>s. <br/>";
            }
            if (!empty($metrics['time_additional'])) {
                $metricsHtml .= "Queue + Install: <b>{$metrics['time_additional']}}</b>s.";
            } elseif (!empty($metrics['time_queueing']) && !empty($metrics['time_install'])) {
                $metricsHtml .= "Queue: <b>{$metrics['time_queueing']}</b>s. Install: <b>{$metrics['time_install']}</b>s.";
            }

            return $metricsHtml;
        }
    ),
    'container' => [
        'singletons' => [
            Notifier::class => [
                'class' => Notifier::class,
                'channels' => [
                    'mail' => [
                        'class' => MailChannel::class,
                        'from' => 'noreply@example.com',
                    ],
                ],
                'on afterSend' => [NotificationEventHandler::class, 'afterSend']
            ],
            'notificationService' => NotificationServiceInterface::class,
            NotificationServiceInterface::class =>[
                'class' => NotificationService::class,
                'releaseRequestEmail'           => 'noreply@example.com',
                'releaseRequestForbiddenEmail'  => 'noreply@example.com',
                'usingSucceedEmail'             => 'noreply@example.com',
            ],
            DeployServiceInterface::class => [
                'class' => DeployService::class,
            ],
            CronConfigProcessingStrategyInterface::class => [
                'class' => CronConfigNoProcessingStrategy::class,
            ],
            LoggerInterface::class => function () {
                $loggerConfig = Yii::$app->params['logger'];
                $processors = $loggerConfig['processors'] ?: [];
                $handlers = $loggerConfig['handlers'] ?: [];

                $logger = new Logger('main');

                foreach ($processors as $processor) {
                    if ($processor instanceof ProcessorInterface) {
                        $logger->pushProcessor($processor);
                    }
                }

                foreach ($handlers as $handler) {
                    if (is_callable($handler)) {
                        $handler = call_user_func($handler);
                    }
                    if ($handler instanceof HandlerInterface) {
                        $logger->pushHandler($handler);
                    }
                }

                return $logger;
            },
            MigrationLogAggregatorUrlInterface::class => [
                ['class' => MigrationLogAggregatorUrl::class],
                ['http://migration-logger-aggregator.url?migration_name=#migration_name#&migration_type=#migration_type#&migration_project=#migration_project#'],
            ],
        ],
    ],
    'on ' . Application::EVENT_BEFORE_REQUEST => function () {
        // We're in web app, not in console
        if (Yii::$app instanceof \yii\web\Application) {
            $user = Yii::$app->getUser();
            if (!$user->getIsGuest()) {
                /** @var User $identity */
                $identity = $user->getIdentity();
                if ($identity->profile->hasAttribute('locale')) {
                    $locale = $identity->profile->locale;
                    if (!empty($locale)) {
                        Yii::$app->language = $locale;
                    }
                }
            }
        }

    },
);

if (class_exists(\yii\debug\Module::class)) {
    array_unshift($config['bootstrap'], 'debug');
    $config['modules']['debug'] = [
        'class' => yii\debug\Module::class,
        'allowedIPs' => ['10.0.2.2', '::1'],
        'fileMode' => 0777,
        'dirMode' => 0777,
    ];
}

// ag: db_admin - DB role for migrations
$config['components']['db_admin'] = $config['components']['db'];

if (file_exists(__DIR__ . "/../../config.local.php")) {
    require(__DIR__ . "/../../config.local.php");
}

return $config;
