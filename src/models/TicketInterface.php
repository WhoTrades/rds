<?php
/**
 * Interface for Jira (or whatever) tickets for notifications
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\models;

interface TicketInterface
{
    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @return string
     */
    public function getTicketKey(): string;

    /**
     * @return string
     */
    public function getSummary(): string;
}
