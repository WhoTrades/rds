<?php
namespace app\components;

use ServiceBase_IDebugLogger;
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
    }

    /**
     * {@inheritdoc}
     */
    public function runAction($route, $params = [])
    {
        return new Response();
    }
}
