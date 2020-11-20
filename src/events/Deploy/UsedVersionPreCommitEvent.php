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

    /** @var string */
    private $projectPreviousVersion;

    /**
     * UsedVersionPreCommitEvent constructor.
     *
     * @param ReleaseRequestUsedVersion $message
     * @param ReleaseRequest $releaseRequest
     * @param string $projectPreviousVersion
     * @param null $config
     */
    public function __construct(ReleaseRequestUsedVersion $message, ReleaseRequest $releaseRequest, string $projectPreviousVersion, $config = null)
    {
        $config = $config ?? [];
        parent::__construct($config);

        $this->message = $message;
        $this->releaseRequest = $releaseRequest;
        $this->projectPreviousVersion = $projectPreviousVersion;
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
     * @return string
     */
    public function getProjectPreviousVersion(): string
    {
        return $this->projectPreviousVersion;
    }
}
