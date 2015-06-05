<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Jira_HardMigrationNotifier -vv
 */
class Cronjob_Tool_Jira_HardMigrationNotifier extends RdsSystem\Cron\RabbitDaemon
{
    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $hardMigrations = HardMigration::model()->findAllByAttributes([
            'migration_status' => [
                HardMigration::MIGRATION_STATUS_NEW,
                HardMigration::MIGRATION_STATUS_FAILED,
                HardMigration::MIGRATION_STATUS_PAUSED,
                HardMigration::MIGRATION_STATUS_STOPPED,
            ],
            'migration_environment' => 'main',
        ]);

        $tickets = [];
        foreach ($hardMigrations as $migration) {
            /** @var $migration HardMigration */

            $project = explode("-", $migration->migration_ticket)[0];
            if (!in_array($project, Yii::app()->params['jiraProjects'])) {
                $this->debugLogger->message("Unknown project $project of migration ".$migration->migration_name.", skip it");
                continue;
            }
            $tickets[] = $migration->migration_ticket;
        }

        if (!$tickets) {
            $this->debugLogger->message("No tickets");
            return;
        }

        $jiraApi = new JiraApi($this->debugLogger);
        foreach ($tickets as $ticket) {
            $this->debugLogger->message("Processing ticket $ticket");
            $ticketInfo = $jiraApi->getTicketInfo($ticket);

            $comments = $ticketInfo['fields']['comment']['comments'];
            $lastComment = end($comments);
            $url = "https://".$_SERVER['HTTP_HOST']."/hardMigration/index?HardMigration%5Bmigration_ticket%5D=$ticket&HardMigration%5Bmigration_environment%5D=main";
            $message = "В задаче есть незавершенные тяжелые миграции: ";
            if ($lastComment && preg_match('~^(Обновление\((\d+)\): )?'.preg_quote($message).'~sui', $lastComment['body'], $ans)) {
                $index = isset($ans[2]) ? $ans[2] : 0;
                $index++;
                $jiraApi->updateComment($ticket, $lastComment['id'], "Обновление($index): $message$url");
            } else {
                $jiraApi->addComment($ticket, $message.$url);
            }

        }
    }
}
