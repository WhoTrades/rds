<?php
namespace app\components;

use Yii;
use ServiceBase_IDebugLogger;
use yii\base\Controller;

class ConsoleApplication extends \yii\console\Application
{
    public $user;

    /**
     * ConsoleApplication constructor.
     *
     * @param array                    $config
     * @param ServiceBase_IDebugLogger $debugLogger
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->controller = Yii::createObject(Controller::class, [null, $this, 'route' => [null, null]]);

        Yii::setAlias('@webroot', '/');
        Yii::setAlias('@web', '/');
    }

    /**
     * {@inheritdoc}
     */
    public function runAction($route, $params = [])
    {
        return new ConsoleResponse();
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
