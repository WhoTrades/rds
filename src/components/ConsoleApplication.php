<?php
namespace app\components;

use Yii;
use ServiceBase_IDebugLogger;

class ConsoleApplication extends \yii\console\Application
{
    public $user;

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

        Yii::setAlias('@webroot', '/');
        Yii::setAlias('@web', '/');
    }
}
