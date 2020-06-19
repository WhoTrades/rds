<?php
/**
 * Release request was used notification
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification;

use tuyakhov\notifications\messages\MailMessage;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\TicketInterface;
use whotrades\rds\services\notification\template;
use TypeError;

class UsingSucceed extends BaseNotification
{
    /**
     * @var Project
     */
    protected $project;

    /**
     * @var ReleaseRequest
     */
    protected $releaseRequestNew;

    /**
     * @var ReleaseRequest
     */
    protected $releaseRequestOld;
    /**
     * @var string
     */
    protected $versionNew;

    /**
     * @var string
     */
    protected $versionOld;

    /**
     * @var TicketInterface[]
     */
    protected $ticketList;

    /**
     * @param Project $project
     * @param ReleaseRequest $releaseRequestNew
     * @param ReleaseRequest $releaseRequestOld
     * @param string $versionNew
     * @param string $versionOld
     * @param TicketInterface[] $ticketList
     */
    public function __construct(Project $project, ReleaseRequest $releaseRequestNew, ReleaseRequest $releaseRequestOld, array $ticketList = null)
    {
        $ticketList = $ticketList ?? [];
        array_walk(
            $ticketList,
            function ($value, $key) {
                if (!$value instanceof TicketInterface) {
                    $typeRequired = TicketInterface::class;
                    $typeGiven = gettype();
                    if (is_object($value)) {
                        $typeGiven = get_class($value);
                    }
                    throw new TypeError("All values of \$ticketList must be of the type {$typeRequired}, {$typeGiven} given with {$key} index");
                }
            }
        );

        $this->project = $project;
        $this->releaseRequestNew = $releaseRequestNew;
        $this->releaseRequestOld = $releaseRequestOld;
        $this->versionNew = $this->releaseRequestNew->rr_build_version;
        $this->versionOld = $this->releaseRequestOld->rr_build_version;
        $this->ticketList = $ticketList;
    }

    public function exportForMail(): MailMessage
    {
        $subject = "[RDS] Состоялся релиз {$this->project->project_name} - {$this->versionNew}";
        $body = "Состоялся релиз {$this->project->project_name}  - {$this->versionNew}<br />";
        $body .= (new template\TicketListEmail($this->releaseRequestNew->obj_id, $this->ticketList))->generate();

        return $this->generateMailMessage($subject, $body);
    }
}