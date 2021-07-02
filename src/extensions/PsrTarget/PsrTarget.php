<?php
/**
 * @author Maksim Rodikov
 */

namespace whotrades\rds\extensions\PsrTarget;

use Psr\Log\LoggerInterface;

class PsrTarget extends \samdark\log\PsrTarget
{
    /**
     * PsrTarget constructor.
     *
     * @param LoggerInterface $logger
     * @param array|null $config
     */
    public function __construct(LoggerInterface $logger, $config = null)
    {
        $config = $config ?? [];
        parent::__construct($config);
        $this->setLogger($logger);
    }

}
