<?php
/**
 * @author Maksim Rodikov
 */
declare(strict_types=1);

namespace whotrades\rds\components\Deploy;

interface DeployEventInterface
{
    const EVENT_TASK_STATUS_CHANGED_BEFORE      = 'deploy_task_status_changed_before';
    const EVENT_TASK_STATUS_CHANGED_AFTER       = 'deploy_task_status_changed_after';
    const EVENT_CRON_CONFIG_BEFORE              = 'deploy_cron_config_before';
    const EVENT_CRON_CONFIG_PRE_COMMIT_HOOK     = 'deploy_cron_config_pre_commit';
    const EVENT_CRON_CONFIG_AFTER               = 'deploy_cron_config_after';
    const EVENT_TASK_USE_ERROR_BEFORE           = 'deploy_task_use_error_before';
    const EVENT_TASK_USE_ERROR_AFTER            = 'deploy_task_use_error_after';
    const EVENT_USED_VERSION_BEFORE             = 'deploy_used_version_before';
    const EVENT_USED_VERSION_AFTER              = 'deploy_used_version_after';
    const EVENT_USED_VERSION_PRE_COMMIT_HOOK    = 'deploy_used_version_pre_commit';
    const EVENT_PROJECT_CONFIG_RESULT_BEFORE    = 'deploy_project_config_result_before';
    const EVENT_PROJECT_CONFIG_RESULT_AFTER     = 'deploy_project_config_result_after';
    const EVENT_REMOVE_RELEASE_REQUEST_BEFORE   = 'deploy_remove_release_request_before';
    const EVENT_REMOVE_RELEASE_REQUEST_AFTER    = 'deploy_remove_release_request_after';
}
