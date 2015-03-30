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
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraCreateVersion::getPgqConsumer('rds_jira_create_version', 'rds_jira_create_version_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraCommit::getPgqConsumer('rds_jira_commit', 'rds_jira_commit_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraTicketStatus::getPgqConsumer('rds_jira_commit', 'rds_jira_ticket_status_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraUse::getPgqConsumer('rds_jira_use', 'rds_jira_use_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_JiraMoveTicket::getPgqConsumer('rds_jira_move_ticket', 'rds_jira_move_ticket_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_JiraFixVersionsRelease::getToolCommand([], $verbosity=1), $delay=3600)),
            new CronCommand(Cronjob_Tool_JiraMergeTasks::getToolCommand(['--max-duration=60'], $verbosity=1)),
            new CronCommand(Cronjob_Tool_JiraCloseFeatures::getToolCommand([], $verbosity=1), '10 * * * *'),

            new Comment("TeamCity integration"),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsTeamCityRunTest::getPgqConsumer('rds_teamcity_run_test', 'rds_teamcity_run_test_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsTeamCityBuildComplete::getPgqConsumer('rds_teamcity_build_complete', 'rds_teamcity_build_complete_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new CronCommand(Cronjob_Tool_TeamCityCheckQueuedTasks::getToolCommand([], $verbosity=1), '* * * * *'),

            new Comment("Deployment"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_Deploy::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_Merge::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),

            new Comment("Hard migrations"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigration::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigration::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationProgress::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationProgress::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationLogChunk::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigrationLogChunk::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),
            new CronCommand(Cronjob_Tool_HardMigrationStarter::getToolCommand([], $verbosity=1)),

            new Comment("Maintenance tools"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_MaintenanceTool::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_MaintenanceTool::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),

            new CronCommand(Cronjob_Tool_MaintenanceToolRun::getToolCommand(['--tool-name=ImportDataFromProdToPreprod --env=preprod'], $verbosity=1), '1 1 * * 6'),     //an: каждую субботу утром
            new CronCommand(Cronjob_Tool_MaintenanceToolRun::getToolCommand(['--tool-name=systemTest --env=main'], $verbosity=1), '*/10 * * * *'),     //an: для проверки работоспособности системы запуска тулов
            new CronCommand(new PeriodicCommand(Cronjob_Tool_Maintenance_MasterTool::getToolCommand(['--max-duration=60'], $verbosity=1), $delay = 0)),

            new Comment("Misc"),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_RdsAlertStatus::getToolCommand([], $verbosity=1), $delay = 5)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_HardMigrationLogRotator::getToolCommand([], $verbosity=1), $delay = 30)),
            new CronCommand(Cronjob_Tool_GitDropFeatureBranch::getToolCommand([], $verbosity=3), '10 0 * * *'),
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