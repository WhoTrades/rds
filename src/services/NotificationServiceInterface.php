<?php
/**
 * Interface of outer notifications service
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services;

use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;

interface NotificationServiceInterface
{
    /**
     * @param string $projectName
     * @param string $userName
     * @param string $comment
     */
    public function sendBuildStarted(string $projectName, string $userName, string $comment): void;

    /**
     * @param string $projectName
     * @param int $buildId
     * @param int $failCount
     */
    public function sendBuildFailed(string $projectName, int $buildId, int $failCount): void;

    /**
     * @param string $projectName
     * @param string $version
     * @param array $buildIdList
     *
     * @return void
     */
    public function sendInstallationSucceed(string $projectName, string $version, array $buildIdList): void;

    /**
     * @param string $projectName
     * @param int $buildId
     *
     * @return void
     */
    public function sendInstallationCanceled(string $projectName, int $buildId): void;

    /**
     * @param string $projectName
     * @param int $buildId
     * @param int $failCount
     */
    public function sendInstallationFailed(string $projectName, int $buildId, int $failCount): void;

    /**
     * @param Project $project
     * @param ReleaseRequest $releaseRequestNew
     * @param ReleaseRequest $releaseRequestOld
     * @param string $initiatorUserName
     */
    public function sendUsingSucceed(Project $project, ReleaseRequest $releaseRequestNew, ReleaseRequest $releaseRequestOld, string $initiatorUserName): void;

    /**
     * @param string $projectName
     * @param string $user
     * @param string $comment
     */
    public function sendReleaseRequestForbidden(string $projectName, string $user, string $comment): void;
}