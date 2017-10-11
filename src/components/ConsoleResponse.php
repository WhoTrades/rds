<?php
namespace whotrades\rds\components;

use yii\console\Response;

class ConsoleResponse extends Response
{
    public $statusCode = null;

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
