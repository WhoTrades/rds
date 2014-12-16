<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=JiraMergeTasks -vv
 */
class Cronjob_Tool_JiraMergeTasks extends RdsSystem\Cron\RabbitDaemon
{
    //an: Интервал, с которым мы пытается заново смержить задачу. Например, если разработчик разрулит конфликты - мы сами
    //это заметим и передвинем задачу в следующий статус

    const MERGE_INTERVAL = 600;
    const LABEL_MERGING = "merging";

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        if (!\Config::getInstance()->serviceRds['jira']['mergeTasks']) {
            $this->debugLogger->message("Tool disabled by config");
            return;
        }
        $model = $this->getMessagingModel($cronJob);
        $jira = new JiraApi($this->debugLogger);
        $map = [
            \Jira\Status::STATUS_MERGE_TO_DEVELOP => "develop",
            \Jira\Status::STATUS_MERGE_TO_STAGING => "staging",
            \Jira\Status::STATUS_MERGE_TO_MASTER => "master",
        ];

        foreach ($map as $status => $branch) {
            $this->debugLogger->message("Processing branch $branch");
            $list = $jira->getTicketsByStatus($status);

            foreach ($list['issues'] as $ticketInfo) {
                $ticket = $ticketInfo['key'];
                $jiraFeatures = JiraFeature::model()->findAllByAttributes(['jf_ticket' => $ticket]);
                if (empty($jiraFeatures)) {
                    $jira->addComment($ticket, "Задача не была слита, так как по ней никто не стартовал фичу с помощью wtflow");
                    continue;
                }

                $field = "jf_last_merge_request_to_{$branch}_time";
                if (time() - strtotime($jiraFeatures[0]->$field) < self::MERGE_INTERVAL) {
                    $this->debugLogger->message("Skip requesting merge $ticket to $branch, because it's to less time from last request");
                    continue;
                }

                $this->debugLogger->message("Sending merge task of ticket $ticket to $branch");
                $model->sendMergeTask(new Message\Merge\Task($jiraFeatures[0]->obj_id, $jiraFeatures[0]->jf_branch, $branch));

                JiraFeature::model()->updateAll([$field => date("Y-m-d H:i:s")], "jf_ticket=:ticket", [':ticket' => $ticket]);
            }
        }
    }
}
