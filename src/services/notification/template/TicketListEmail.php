<?php
/**
 * Generates ticket list for email notifications
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification\template;

class TicketListEmail extends TicketListAbstract
{
    /**
     * {@inheritDoc}
     */
    public function generate(): string
    {
        if (!$this->ticketList) {
            return '';
        }

        $result = $this->header ?? self::HEADER_DEFAULT;
        $result .= "<br /><table>\r\n";
        foreach ($this->ticketList as $ticket) {
            $result .= "<tr><td><a href='{$ticket->getUrl()}'><b>{$ticket->getTicketKey()}</b></a></td><td> &ndash; {$ticket->getSummary()}</td></tr>\r\n";
        }
        $result .= "</table>\r\n";

        return $result;
    }
}