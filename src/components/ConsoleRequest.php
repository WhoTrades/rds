<?php
/**
 * Класс, который объединяет в себе поведение как консольного, так и http запроса. Для http пишем просто загрушки, что бы
 * могла обрабатывать логика генерации html кода
 */
namespace app\components;

use yii\console\Request;

class ConsoleRequest extends Request
{
    public $url = '/';
    /**
     * @return array
     */
    public function getQueryParams()
    {
        return [];
    }

    public function getAbsoluteUrl()
    {
        return "console://" . implode("_", $_SERVER['argv']);
    }

    public function getRawBody()
    {
        return null;
    }

    public function getIsAjax()
    {
        return false;
    }

    public function getMethod()
    {
        return 'console';
    }

    public function getUserIP()
    {
        return null;
    }

    public function getHeaders()
    {
        return [];
    }
}
