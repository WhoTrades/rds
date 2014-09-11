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
        if(!is_dir($path)) {
            mkdir($path, 0777);
        }

        return parent::setRuntimePath($path);
    }
}
