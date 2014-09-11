<?php
class ExternalApplication extends CApplication
{
    public $theme;

    public function processRequest()
    {
        //ничего не делаем
    }

    public function setRuntimePath($path)
    {
        //an: Создаем папку для временных файлов, если её ещё нету
        if((false !== $runtimePath=realpath($path)) && !is_dir($runtimePath)) {
            mkdir($runtimePath, 0777);
        }

        return parent::setRuntimePath($path);
    }
}
