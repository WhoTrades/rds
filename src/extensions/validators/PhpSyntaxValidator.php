<?php
namespace whotrades\rds\extensions\validators;

use whotrades\RdsSystem\lib\Exception\CommandExecutorException;
use yii\validators\Validator;
use Exception;

/**
 * Валидация синтаксиса PHP-кода
 *
 * PHP version 5.4
 *
 * @author Dmitry Glizhinskiy <dg@whotrades.org>
 * @copyright © 2015 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */

class PhpSyntaxValidator extends Validator
{
    /**
     * @param \yii\base\Model $model
     * @param string $attribute
     * @throws Exception
     */
    public function validateAttribute($model, $attribute)
    {
        $tempName = tempnam(sys_get_temp_dir(), $attribute);
        $result = file_put_contents($tempName, $model->$attribute);

        if ($result === false) {
            throw new Exception("Не могу записать в файл $tempName");
        }

        $commandExecutor = new \whotrades\RdsSystem\lib\CommandExecutor();

        try {
            $command = PHP_BINDIR . DIRECTORY_SEPARATOR . "php -l -ddisplay_errors=On $tempName 2>&1";
            $commandExecutor->executeCommand($command);
        } catch (CommandExecutorException $exception) {
            $errorMessage = 'Ошибка синтаксиса валидации PHP-кода:' . PHP_EOL;
            $errorMessage .= $exception->getOutput();
            $errorMessage = str_replace($tempName, "\"$attribute\"", $errorMessage);
            $model->addError($attribute, nl2br($errorMessage));
        }

        unlink($tempName);
    }
}
