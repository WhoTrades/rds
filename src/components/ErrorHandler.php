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
        /** @var $sentry \mito\sentry\Component */
        $sentry = Yii::$app->sentry;
        $sentry->captureException($exception);
    }
}
