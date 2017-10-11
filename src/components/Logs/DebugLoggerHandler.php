<?php
/**
 * @author Artem Naumenko
 * Класс, который реализует хендлер логов для монолога, и пишет логи внутрь Yii::log()
 */

namespace whotrades\rds\components\Logs;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class DebugLoggerHandler extends AbstractProcessingHandler
{
    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write(array $record)
    {
        $message = trim((string) $record['formatted']);

        switch ($record['level']) {
            case Logger::DEBUG:
                \Yii::trace($message);
                break;

            case Logger::INFO:
                \Yii::info($message);
                break;

            case Logger::NOTICE:
                \Yii::trace($message);
                break;

            case Logger::WARNING:
                \Yii::warning($message);
                break;

            default:
                // an: все остальное - ошибка
                \Yii::error($message);
        }
    }
}
