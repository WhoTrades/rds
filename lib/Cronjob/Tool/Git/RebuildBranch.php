<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Git_RebuildBranch -vv
 */

use RdsSystem\Message;
use RdsSystem\lib\CommandExecutor;

class Cronjob_Tool_Git_RebuildBranch extends RdsSystem\Cron\RabbitDaemon
{
    public static function getCommandLineSpec()
    {
        return [
            'branch' => [
                'desc' => 'Branch to rebuild, develop|staging',
                'valueRequired' => true,
            ],
        ] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model = $this->getMessagingModel($cronJob);
        $branch = $cronJob->getOption('branch');

        if (!in_array($branch, ['develop', 'staging'])) {
            throw new \Cronjob\Exception\IllegalOption('Branch must be develop or staging only');
        }

        $map = [
            'develop' => [
                \Jira\Status::STATUS_MERGE_TO_DEVELOP,
                \Jira\Status::STATUS_WAITING_FOR_TEST,
                \Jira\Status::STATUS_TESTING,

                \Jira\Status::STATUS_MERGE_TO_STAGING,
                \Jira\Status::STATUS_READY_FOR_CHECK,
                \Jira\Status::STATUS_CHECKING,
            ],
            'staging' => [
                \Jira\Status::STATUS_MERGE_TO_STAGING,
                \Jira\Status::STATUS_READY_FOR_CHECK,
                \Jira\Status::STATUS_CHECKING,
            ],
        ];

        $jira = new JiraApi($this->debugLogger);

        $jql = "status IN (\"".implode('", "', $map[$branch])."\") AND project IN (".implode(", ", Yii::app()->params['jiraProjects']).")";
        $this->debugLogger->message("Getting tickets by jql=$jql");

        $tickets = $jira->getTicketsByJql($jql);

        $branches = [];
        foreach ($tickets['issues'] as $ticket) {
            $this->debugLogger->debug("Testing {$ticket['key']}");
            if (!JiraFeature::model()->countByAttributes(['jf_ticket' => $ticket['key']])) {
                $this->debugLogger->debug("Skip {$ticket['key']} as it is not known");
                continue;
            }

            $branches[] = "feature/{$ticket['key']}";
        }

        $this->debugLogger->message("Branches: ".implode(", ", $branches));

        $build = new GitBuild();
        $build->branch = "tmp/build_".uniqid();
        $build->status = GitBuild::STATUS_NEW;
        $build->additional_data = $branch;
        $build->save();

        $this->debugLogger->message("Target branch: $build->branch, sending create branch task");

        $model->sendMergeCreateBranch(new Message\Merge\CreateBranch($build->branch, "master", false));

        foreach ($branches as $val) {

            if (!GitBuildBranch::model()->countByAttributes([
                'git_build_id' => $build->obj_id,
                'branch' => $val,
            ])) {
                $b = new GitBuildBranch();
                $b->git_build_id = $build->obj_id;
                $b->branch = $val;
                $b->status = GitBuildBranch::STATUS_NEW;
                $b->errors = '[]';
                $b->save();
            }


            $this->debugLogger->message("Branch: $val, sending merge branch task");
            $model->sendMergeTask(new Message\Merge\Task($build->obj_id, $val, $build->branch, Message\Merge\Task::MERGE_TYPE_BUILD));
        }

        $this->debugLogger->message("Tasks sent successfully");
    }
}

