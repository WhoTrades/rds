<?php
namespace app\components;

use Yii;
use yii\web\HttpException;

class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @param \Exception $exception
     */
    public function logException($exception)
    {
        if ($exception instanceof HttpException) {
            Yii::$app->debugLogger->dump()->exception('an', $exception)->notice()->save();
        } else {
            Yii::$app->debugLogger->dump()->exception('an', $exception)->critical()->save();
        }
    }
}
