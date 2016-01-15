<?php
/**
 * Валидация синтаксиса PHP-кода
 *
 * PHP version 5.4
 *
 * @author Dmitry Glizhinskiy <dg@whotrades.org>
 * @copyright © 2015 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */

class PhpSyntaxValidator extends CValidator
{
    /**
     * @param CModel $object
     * @param string $attribute
     *
     * @throws CException
     */
    protected function validateAttribute($object, $attribute)
    {
        $tempName = tempnam(sys_get_temp_dir(), $attribute);
        $result = file_put_contents($tempName, $object->$attribute);

        if ($result === false) {
            throw new CException("Не могу записать в файл $tempName");
        }

        $commandExecutor = new \RdsSystem\lib\CommandExecutor(Yii::app()->debugLogger);

        try {
            $command = PHP_BINDIR.DIRECTORY_SEPARATOR."php -l $tempName 2>&1";
            $commandExecutor->executeCommand($command);
        } catch (\RdsSystem\lib\CommandExecutorException $exception) {
            $errorMessage = 'Ошибка синтаксиса валидации PHP-кода:'.PHP_EOL;
            $errorMessage .= $exception->getOutput();
            $errorMessage = str_replace($tempName, "\"$attribute\"", $errorMessage);
            $object->addError($attribute, nl2br($errorMessage));
        }

        unlink($tempName);
    }
}