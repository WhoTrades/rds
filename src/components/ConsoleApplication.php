<?php
namespace app\components;

use Yii;
use yii\base\Controller;

class ConsoleApplication extends \yii\console\Application
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

        //$this->controller = Yii::createObject(Controller::class, [null, $this, 'route' => [null, null]]);

        Yii::setAlias('@webroot', '/');
        Yii::setAlias('@web', '/');
    }

    /**
     * @return array
     */
    public function coreCommands()
    {
        return [
            'help' => 'yii\console\controllers\HelpController',
            'migrate' => 'yii\console\controllers\MigrateController',
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
