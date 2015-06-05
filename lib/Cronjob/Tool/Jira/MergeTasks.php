<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Jira_MergeTasks -vv
 */
class Cronjob_Tool_Jira_MergeTasks extends RdsSystem\Cron\RabbitDaemon
{
    //an: Интервал, с которым мы пытается заново смержить задачу. Например, если разработчик разрулит конфликты - мы сами
    //это заметим и передвинем задачу в следующий статус

    const MERGE_INTERVAL = 3600;
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

        if (GitBuild::model()->countByAttributes(['status' => GitBuild::STATUS_NEW])) {
            $this->debugLogger->message("Skip merge tasks as exists build in progress");

            return;
        }

        $model = $this->getMessagingModel($cronJob);
        $jira = new JiraApi($this->debugLogger);
        $map = [
            \Jira\Status::STATUS_MERGE_TO_DEVELOP => "develop",
            \Jira\Status::STATUS_MERGE_TO_STAGING => "staging",
            \Jira\Status::STATUS_MERGE_TO_MASTER => "master",
        ];
        $transitionMap = [
            "develop" => \Jira\Transition::MERGED_TO_DEVELOP,
            "master" => \Jira\Transition::MERGED_TO_MASTER,
            "staging" => \Jira\Transition::MERGED_TO_STAGING,
        ];

        foreach ($map as $status => $branch) {
            $this->debugLogger->message("Processing branch $branch");
            $list = $jira->getTicketsByStatus($status, Yii::app()->params['jiraProjects']);

            foreach ($list['issues'] as $ticketInfo) {
                $ticket = $ticketInfo['key'];
                $this->debugLogger->message("Processing ticket $ticket as branch $branch");

                $jiraFeatures = JiraFeature::model()->findAllByAttributes(['jf_ticket' => $ticket]);
                if (empty($jiraFeatures)) {
                    $this->debugLogger->message("Ticket was moved to merge without wtflow, move it to next status without merge");
                    $transition = $transitionMap[$branch];
                    $jira->addTicketLabel($ticket, "no-wtflow");
                    $jira->transitionTicket($ticketInfo, $transition, null, true);
                    $jira->addComment($ticket, "(!) Задача была сделана по старой схеме, не с помощью wtflow. Она не была слита в ветки develop/staging, так как нет веток. При не попадании кода на dev/tst/prod контура обращайтесь к разработчику, ответственному за задачу");
                    $lastDeveloper = $jira->getLastDeveloperNotRds($ticketInfo);

                    $this->debugLogger->message("Assign back to $lastDeveloper");

                    $jira->assign($ticket, $lastDeveloper);
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
