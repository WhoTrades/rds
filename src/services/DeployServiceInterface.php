<?php
declare(strict_types=1);

namespace whotrades\rds\services;

use whotrades\RdsSystem\Message\ReleaseRequestCronConfig;
use whotrades\RdsSystem\Message\ReleaseRequestUsedVersion;

interface DeployServiceInterface
{
    /**
     * @param ReleaseRequestCronConfig $message
     */
    public function setCronConfig(ReleaseRequestCronConfig $message): void;

    /**
     * @param ReleaseRequestUsedVersion $message
     */
    public function setUsedVersion(ReleaseRequestUsedVersion $message): void;
}
