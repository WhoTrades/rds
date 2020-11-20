<?php
declare(strict_types=1);

namespace whotrades\rds\events\Deploy;

use whotrades\RdsSystem\Message\ReleaseRequestCronConfig;
use yii\base\Event as BaseEvent;

final class CronConfigBeforeEvent extends BaseEvent
{
    /** @var ReleaseRequestCronConfig */
    private $message;

    /**
     * CronConfigBeforeEvent constructor.
     *
     * @param ReleaseRequestCronConfig $message
     * @param null $config
     */
    public function __construct(ReleaseRequestCronConfig $message, $config = null)
    {
        $config = $config ?? [];
        parent::__construct($config);

        $this->message = $message;
    }

    /**
     * @return ReleaseRequestCronConfig
     */
    public function getMessage(): ReleaseRequestCronConfig
    {
        return $this->message;
    }
}
