<?php
class WebApplication extends CWebApplication
{
    /** @var \ServiceBase_IDebugLogger */
    public $debugLogger;
    public $preloadedModules = [];

    public function end($status=0,$exit=true)
    {
        if ($exit) {
            CoreLight::getInstance()->getFatalWatcher()->stop();
        }

        return parent::end();
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
        if(!is_dir($path)) {
            mkdir($path, 0777);
        }

        return parent::setRuntimePath($path);
    }

    protected function init()
    {
        //ob_get_clean();
        error_reporting(E_ALL);
        foreach ($this->preloadedModules as $module) {
            var_dump($this->getModule($module));
        }

        parent::init();
    }
}
