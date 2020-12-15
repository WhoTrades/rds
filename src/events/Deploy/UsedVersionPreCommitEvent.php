<?php
declare(strict_types=1);

namespace whotrades\rds\events\Deploy;

use whotrades\rds\models\ReleaseRequest;
use whotrades\RdsSystem\Message\ReleaseRequestUsedVersion;
use yii\base\Event as BaseEvent;

final class UsedVersionPreCommitEvent extends BaseEvent
{
    /** @var ReleaseRequestUsedVersion */
    private $message;

    /** @var ReleaseRequest */
    private $releaseRequest;

    /** @var ReleaseRequest */
    private $releaseRequestOld;

    /**
     * UsedVersionPreCommitEvent constructor.
     *
     * @param ReleaseRequestUsedVersion $message
     * @param ReleaseRequest $releaseRequest
     * @param ReleaseRequest $releaseRequestOld
     * @param null $config
     */
    public function __construct(ReleaseRequestUsedVersion $message, ReleaseRequest $releaseRequest, ReleaseRequest $releaseRequestOld, $config = null)
    {
        $config = $config ?? [];
        parent::__construct($config);

        $this->message = $message;
        $this->releaseRequest = $releaseRequest;
        $this->releaseRequestOld = $releaseRequestOld;
    }

    /**
     * @return ReleaseRequestUsedVersion
     */
    public function getMessage(): ReleaseRequestUsedVersion
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

    /**
     * @return ReleaseRequest
     */
    public function getReleaseRequestOld(): ReleaseRequest
    {
        return $this->releaseRequestOld;
    }
}
