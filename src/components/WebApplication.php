<?php
class WebApplication extends \yii\web\Application
{
    /** @var \ServiceBase_IDebugLogger */
    public $debugLogger;

    /**
     * @param int  $status
     * @param bool $exit
     *
     * @throws \yii\base\ExitException
     */
    public function end($status = 0, $exit = true)
    {
        if ($exit) {
            CoreLight::getInstance()->getFatalWatcher()->stop();
        }
    }

    /**
     * @param Exception $exception
     */
    public function handleException(Exception $exception)
    {
        if (!$exception instanceof CHttpException) {
            $this->debugLogger->dump()->exception('an', $exception)->critical()->save();
        } else {
            $this->debugLogger->dump()->exception('an', $exception)->notice()->save();
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
        //an: Создаем папку для временных файлов, если её ещё нету
        if (!is_dir($path)) {
            mkdir($path, 0777);
        }

        return parent::setRuntimePath($path);
    }
}
