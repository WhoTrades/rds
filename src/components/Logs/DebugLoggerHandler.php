<?php
/**
 * @author Artem Naumenko
 * Класс, который реализует хендлер логов для монолога, и пишет логи внутрь debugLogger
 */

namespace app\components\Logs;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class DebugLoggerHandler extends AbstractProcessingHandler
{
    /** @var \ServiceBase_IDebugLogger */
    private $debugLogger;

    /**
     * @param \ServiceBase_IDebugLogger $debugLogger
     */
    public function __construct(\ServiceBase_IDebugLogger $debugLogger)
    {
        $this->debugLogger = $debugLogger;

        parent::__construct(Logger::DEBUG);
    }

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
                $this->debugLogger->debug($message);
                break;

            case Logger::INFO:
                $this->debugLogger->info($message);
                break;

            case Logger::NOTICE:
                $this->debugLogger->message($message);
                break;

            case Logger::WARNING:
                $this->debugLogger->warning($message);
                break;

            default:
                // an: все остальное - ошибка
                $this->debugLogger->error($message);
        }
    }
}
