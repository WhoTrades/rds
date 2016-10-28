<?php
class WebApplication extends CWebApplication
{
    /** @var \ServiceBase_IDebugLogger */
    public $debugLogger;

    public function end($status=0,$exit=true)
    {
        if ($exit) {
            CoreLight::getInstance()->getFatalWatcher()->stop();
        }

        return parent::end();
    }

    public function handleException($exception)
    {
        if ($exception instanceof CHttpException) {
            $this->debugLogger->dump()->exception('an', $exception)->notice()->save();
        } elseif ($exception instanceof CHttpException) {
            $this->debugLogger->dump()->exception('an', $exception)->critical()->save();
        } elseif ($exception instanceof Error) {
            $this->debugLogger->dump()->message('error', $exception->getMessage(), true, [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ])->critical()->save();
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
}
