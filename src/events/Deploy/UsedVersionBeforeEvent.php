<?php
declare(strict_types=1);

namespace whotrades\rds\events\Deploy;

use whotrades\RdsSystem\Message\ReleaseRequestUsedVersion;
use yii\base\Event as BaseEvent;

final class UsedVersionBeforeEvent extends BaseEvent
{
    /** @var ReleaseRequestUsedVersion */
    private $message;

    /**
     * UsedVersionBeforeEvent constructor.
     *
     * @param ReleaseRequestUsedVersion $message
     * @param null $config
     */
    public function __construct(ReleaseRequestUsedVersion $message, $config = null)
    {
        $config = $config ?? [];
        parent::__construct($config);

        $this->message = $message;
    }

    /**
     * @return ReleaseRequestUsedVersion
     */
    public function getMessage(): ReleaseRequestUsedVersion
    {
        return $this->message;
    }
}
