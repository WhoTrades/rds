<?php
/**
 * Release request was rolled back notification
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification;

use tuyakhov\notifications\messages\MailMessage;
use whotrades\rds\services\notification\template;

class RollBackSucceed extends UsingSucceed
{
    public function exportForMail(): MailMessage
    {
        $subject = "[RDS] ОТКАТ релиза {$this->project->project_name} - {$this->versionOld}";
        $body = "Состоялся откат релиза {$this->project->project_name} - {$this->versionOld} до версии {$this->versionNew}";
        $body .= (new template\TicketListEmail($this->releaseRequestNew->obj_id, $this->ticketList))->generate();

        return $this->generateMailMessage($subject, $body);
    }
}