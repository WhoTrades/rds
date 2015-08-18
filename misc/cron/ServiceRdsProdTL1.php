<?php
use \Cronjob\ConfigGenerator;
use \Cronjob\ConfigGenerator\Comment;
use \Cronjob\ConfigGenerator\MultiCronCommand;
use \Cronjob\ConfigGenerator\CronCommand;
use \Cronjob\ConfigGenerator\SimpleCommand;
use \Cronjob\ConfigGenerator\PeriodicCommand;
use \Cronjob\ConfigGenerator\MultiCommandToCron;
use \Cronjob\ConfigGenerator\MultiPeriodicCommand;

/** @example sphp dev/services/rds/misc/tools/runner.php --tool=CodeGenerate_CronjobGenerator -vv --project=service-rds --env=prod --server=1 */

class ServiceRdsProdTL1
{
    public function getCronConfigRows()
    {
        $allCommands = $this->getAllCommands();

        $allCommands = new MultiCronCommand($allCommands);

        $rows = $allCommands->getCronConfigRows();

        return array_merge($this->getEnv(), $rows);
    }

    protected function getAllCommands()
    {
        return [
            new Comment("JIRA integration"),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraCreateVersion::getPgqConsumer('rds_jira_create_version', 'rds_jira_create_version_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_jira_create_version'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraCommit::getPgqConsumer('rds_jira_commit', 'rds_jira_commit_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_jira_commit'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraTicketStatus::getPgqConsumer('rds_jira_commit', 'rds_jira_ticket_status_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_jira_commit-ticket_status'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraUse::getPgqConsumer('rds_jira_use', 'rds_jira_use_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_jira_use'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_JiraMoveTicket::getPgqConsumer('rds_jira_move_ticket', 'rds_jira_move_ticket_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_jira_move_ticket'),

            new CronCommand(new PeriodicCommand(Cronjob_Tool_Jira_FixVersionsRelease::getToolCommand([], $verbosity=1), $delay=3600), '* * * * *', 'rds_jira_fix_versions_release'),
            new CronCommand(Cronjob_Tool_Jira_MergeTasks::getToolCommand(['--max-duration=60'], $verbosity=1), '* * * * *', 'rds_jira_merge_tasks'),
            new CronCommand(Cronjob_Tool_Jira_CloseFeatures::getToolCommand([], $verbosity=1), '10 * * * *', 'rds_jira_close_features'),
            new CronCommand(Cronjob_Tool_Jira_HardMigrationNotifier::getToolCommand([], $verbosity=1), '10 4 * * *', 'rds_jira_hard_migration_notifier'),

            new Comment("TeamCity integration"),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsTeamCityRunTest::getPgqConsumer('rds_teamcity_run_test', 'rds_teamcity_run_test_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_teamcity_run_test'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsTeamCityBuildComplete::getPgqConsumer('rds_teamcity_build_complete', 'rds_teamcity_build_complete_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_teamcity_build_complete'),
            new CronCommand(Cronjob_Tool_TeamCityCheckQueuedTasks::getToolCommand([], $verbosity=1), '* * * * *', 'rds_team_city_check_queued_tasks'),

            new Comment("Stash integration"),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_ProcessCreatePullRequest::getPgqConsumer('rds_create_pull_request', 'rds_create_pull_request_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_create_pull_request'),

            new Comment("Deployment"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_Deploy::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1), '* * * * *', 'rds_async_reader_deploy'),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_Merge::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1), '* * * * *', 'rds_async_reader_merge'),

            new Comment("Hard migrations"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigration::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1), '* * * * *', 'rds_async_reader_hard_migration-prod'),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationProgress::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1), '* * * * *', 'rds_hard_migration_progress'),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationLogChunk::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1), '* * * * *', 'rds_hard_migration_log_chunk'),

//            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigration::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1), '* * * * *', 'RdsAsyncReader_HardMigration-preprod'),
//            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationProgress::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),
//            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationLogChunk::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),

            new CronCommand(Cronjob_Tool_HardMigrationStarter::getToolCommand([], $verbosity=1), '* * * * *', 'RdsHardMigrationStarter'),

            new Comment("Maintenance tools"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_MaintenanceTool::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1), '* * * * *', 'rds_maintenance_tool'),
            //new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_MaintenanceTool::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),

            //new CronCommand(Cronjob_Tool_MaintenanceToolRun::getToolCommand(['--tool-name=ImportDataFromProdToPreprod --env=preprod'], $verbosity=1), '1 1 * * 6'),     //an: каждую субботу утром
            new CronCommand(Cronjob_Tool_MaintenanceToolRun::getToolCommand(['--tool-name=systemTest --env=main'], $verbosity=1), '*/10 * * * *', 'rds_maintenance_tool_run-system'),     //an: для проверки работоспособности системы запуска тулов
            new CronCommand(new PeriodicCommand(Cronjob_Tool_Maintenance_MasterTool::getToolCommand(['--max-duration=60'], $verbosity=1), $delay = 0), '* * * * *', 'rds_master_tool'),

            new Comment("Misc"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_RdsAlertStatus::getToolCommand([], $verbosity=1), $delay = 5), '* * * * *', 'rds_alert_status'),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_HardMigrationLogRotator::getToolCommand([], $verbosity=1), $delay = 30), '* * * * *', 'rds_hard_migration_log_rotator'),
            new CronCommand(Cronjob_Tool_GitDropFeatureBranch::getToolCommand([], $verbosity=3), '10 0 * * *', 'rds_git_drop_feature_branch'),

            new Comment("Notification"),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraNotificationQueue::getPgqConsumer('rds_jira_notification_queue', 'rds_jira_notification_queue_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *', 'rds_jira_notification_queue'),
        ];
    }

    protected function getEnv()
    {
        return [
            'MAILTO=adm+ny_cron@whotrades.org',
            'CRONJOB_TOOLS=/var/www/service-rds/misc/tools',
        ];
    }
}