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
}
