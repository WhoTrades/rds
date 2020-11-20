<?php
declare(strict_types=1);

namespace whotrades\rds\events\Deploy;

use whotrades\rds\models\ReleaseRequest;
use whotrades\RdsSystem\Message\ReleaseRequestCronConfig;
use yii\base\Event as BaseEvent;

final class CronConfigPreCommitEvent extends BaseEvent
{
    /** @var ReleaseRequestCronConfig */
    private $message;

    /** @var ReleaseRequest */
    private $releaseRequest;

    /**
     * CronConfigPreCommitEvent constructor.
     *
     * @param ReleaseRequestCronConfig $message
     * @param ReleaseRequest $releaseRequest
     * @param null $config
     */
    public function __construct(ReleaseRequestCronConfig $message, ReleaseRequest $releaseRequest, $config = null)
    {
        $config = $config ?? [];
        parent::__construct($config);

        $this->message = $message;
        $this->releaseRequest = $releaseRequest;
    }

    /**
     * @return ReleaseRequestCronConfig
     */
    public function getMessage(): ReleaseRequestCronConfig
    {
        return $this->message;
    }

    /**
     * @return ReleaseRequest
     */
    public function getReleaseRequest(): ReleaseRequest
    {
        return $this->releaseRequest;
    }

}
