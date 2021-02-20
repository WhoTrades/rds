<?php
declare(strict_types=1);

namespace whotrades\rds\services\strategies;

/**
 * Class CronConfigNoProcessingStrategy
 *
 * @package whotrades\rds\services\strategies
 */
class CronConfigNoProcessingStrategy implements CronConfigProcessingStrategyInterface
{

    /**
     * {@inheritdoc}
     */
    public function process(string $cronConfig): string
    {
        return $cronConfig;
    }

}
