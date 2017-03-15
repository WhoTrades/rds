<?php
namespace app\components;

class ConsoleApplication extends \yii\console\Application
{
    /** @var \ServiceBase_IDebugLogger */
    public $debugLogger;

    /**
     * WebApplication constructor.
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
     * @param int  $status
     * @param bool $exit
     *
     * @throws \yii\base\ExitException
     */
    public function end($status = 0, $exit = true)
    {
        if ($exit) {
            \CoreLight::getInstance()->getFatalWatcher()->stop();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleException($exception)
    {
        if ($exception instanceof HttpException) {
            $this->debugLogger->dump()->exception('an', $exception)->notice()->save();
        } else {
            $this->debugLogger->dump()->exception('an', $exception)->critical()->save();
        }

        parent::handleException($exception);
    }

    /**
     * Метод используется в частности при авторизации для формирования имени сессии
     * Так как по дефолту для определения идентификатора приложения используется абсолютный путь - то при каждом релизе происходило вылогинивание
     *
     * @since WTA-45
     * @return string
     */
    public function getId()
    {
        return \Config::getInstance()->project;
    }

    public function setRuntimePath($path)
    {
        // an: Создаем папку для временных файлов, если её ещё нету
        if (!is_dir($path)) {
            mkdir($path, 0777);
        }

        return parent::setRuntimePath($path);
    }

    /**
     * Хак для поддержки старой базы ссылок
     * @param string $route
     * @return \yii\base\Controller
     */
    public function createControllerByID($route)
    {
        $newStyleRoute = preg_replace_callback('~([a-z0-9])([A-Z])~', function ($matches) {
            return $matches[1] . "-" . strtolower($matches[2]);
        }, $route);

        return parent::createControllerByID($newStyleRoute);
    }
}
