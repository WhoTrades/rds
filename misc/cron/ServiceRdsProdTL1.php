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
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraCreateVersion::getPgqConsumer('rds_jira_create_version', 'rds_jira_create_version_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraCommit::getPgqConsumer('rds_jira_commit', 'rds_jira_commit_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraTicketStatus::getPgqConsumer('rds_jira_commit', 'rds_jira_ticket_status_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_RdsJiraUse::getPgqConsumer('rds_jira_use', 'rds_jira_use_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new MultiCommandToCron(new MultiPeriodicCommand(\PgQ_EventProcessor_JiraMoveTicket::getPgqConsumer('rds_jira_move_ticket', 'rds_jira_move_ticket_consumer', 'simple', 'DSN_DB4', 1, [], 3), 5), '* * * * *'),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_JiraFixVersionsRelease::getToolCommand([], $verbosity=1), $delay=60)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_Deploy::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),

            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigration::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=1)),
            new CronCommand(new PeriodicCommand(Cronjob_Tool_AsyncReader_HardMigration::getToolCommand(['--max-duration=60 --env=preprod'], $verbosity=1), $delay=1)),
            new CronCommand(Cronjob_Tool_HardMigrationStarter::getToolCommand([], $verbosity=1)),
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