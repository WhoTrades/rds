<?php
declare(strict_types=1);

namespace whotrades\rds\services\strategies;

/**
 * Interface CronConfigProcessingStrategyInterface
 * Cron textual representation cleanup
 *
 * @package whotrades\rds\services\strategies
 */
interface CronConfigProcessingStrategyInterface
{
    /**
     * @param string $cronConfig
     *
     * @return string
     */
    public function process(string $cronConfig): string;

}
