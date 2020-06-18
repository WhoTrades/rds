<?php
/**
 * Abstract class for different ticket list representations
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification\template;

use whotrades\rds\models\TicketInterface;

abstract class TicketListAbstract
{
    const HEADER_DEFAULT = 'Затронутые задачи:';

    /**
     * @var int
     */
    protected $releaseRequestId;

    /**
     * @var TicketInterface[]
     */
    protected $ticketList;

    /**
     * @var string
     */
    protected $header;

    public function __construct(int $releaseRequestId, array $ticketList, string $header = null)
    {
        $this->releaseRequestId = $releaseRequestId;
        $this->ticketList = $ticketList;
        $this->header = $header;
    }

    /**
     * @return string
     */
    abstract public function generate(): string;
}