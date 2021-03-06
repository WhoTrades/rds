<?php
namespace whotrades\rds\components;

use Yii;
use yii\console\Application;

class ConsoleApplication extends Application
{
    public $user;

    /**
     * ConsoleApplication constructor.
     *
     * @param array                    $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        Yii::setAlias('@webroot', realpath(getcwd() . '/' . 'web'));
        Yii::setAlias('@web', '/');
    }

    /**
     * @return array
     */
    public function coreCommands()
    {
        return [
            'help' => 'yii\console\controllers\HelpController',
        ];
    }

    /**
     * @return array
     */
    public function coreComponents()
    {
        $base = parent::coreComponents();

        $base['request'] = ['class' => ConsoleRequest::class];
        $base['response'] = ['class' => ConsoleResponse::class];

        return $base;
    }
}
