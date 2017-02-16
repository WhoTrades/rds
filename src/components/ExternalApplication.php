<?php
namespace app\components;

class ExternalApplication extends WebApplication
{
    public function processRequest()
    {
        //ничего не делаем
    }

    /**
     * @return null
     */
    public function getTheme()
    {
        return null;
    }
}
