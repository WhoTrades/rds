<?php
/**
 * Plain migration log aggregator url generator
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\components;

use whotrades\RdsSystem\Migration\LogAggregatorUrlInterface;

class MigrationLogAggregatorUrl implements LogAggregatorUrlInterface
{
    /** @var string */
    private $urlTemplate;

    /**
     * @param string $urlTemplate
     */
    public function __construct(string $urlTemplate)
    {
        $this->urlTemplate = $urlTemplate;
    }

    /**
     * {@inheritDoc}
     */
    public function generateFiltered(string $migrationName, string $migrationType, string $migrationProject): string
    {
        // ag: Replace backspace '\' with safe character '_'
        $migrationName = str_replace('\\', '_', $migrationName);
        $filterArray = [
            LogAggregatorUrlInterface::FILTER_MIGRATION_NAME => $migrationName,
            LogAggregatorUrlInterface::FILTER_MIGRATION_TYPE => $migrationType,
            LogAggregatorUrlInterface::FILTER_MIGRATION_PROJECT => $migrationProject,
        ];

        $url = $this->urlTemplate;
        foreach ($filterArray as $key => $value) {
            $url = str_replace("#{$key}#", $value, $url);
        }

        return $url;
    }
}
