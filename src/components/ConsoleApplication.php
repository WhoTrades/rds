<?php
namespace app\components;

use app\controllers\Controller;
use ServiceBase_IDebugLogger;
use Yii;
use yii\console\Response;

class ConsoleApplication extends \yii\console\Application
{
    /** @var \ServiceBase_IDebugLogger */
    public $debugLogger;

    /**
     * ConsoleApplication constructor.
     *
     * @param array                    $config
     * @param ServiceBase_IDebugLogger $debugLogger
     */
    public function __construct(array $config, \ServiceBase_IDebugLogger $debugLogger)
    {
        $this->debugLogger = $debugLogger;

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
        return new Response();
    }

    /**
     * @return array
     */
    public function coreComponents()
    {
        $base = parent::coreComponents();

        $base['request'] = ['class' => ConsoleRequest::class];

        return $base;
    }
}
