<?php
namespace Action\Git;

use RdsSystem\Message;

class RebuildBranch
{
    /**
     * @param string $branch - ветка, которую нужно пересобрать, develop|staging
     * @param string $user - имя пользовеля, инициатора пересборки
     * @param \RdsSystem\Model\Rabbit\MessagingRdsMs $model
     * @throws \ApplicationException
     * @throws \Exception
     */
    public function run($branch, $user, \RdsSystem\Model\Rabbit\MessagingRdsMs $model)
    {
        if (!in_array($branch, ['develop', 'staging'])) {
            throw new \ApplicationException('Branch must be develop or staging only', 404);
        }

        $notReadyBranchesCount = \GitBuild::model()->countByAttributes([
            'additional_data' => $branch,
            'status' => \GitBuild::STATUS_NEW,
        ]);

        if ($notReadyBranchesCount > 0) {
            throw new \ApplicationException("Ветка $branch уже пересобирается, дождитесь окончания процесса прежде чем пересобирать заново", 500);
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

        $jira = new \JiraApi(\Yii::app()->debugLogger);

        $jql = "status IN (\"".implode('", "', $map[$branch])."\") AND project IN (".implode(", ", \Yii::app()->params['jiraProjects']).") ORDER BY updated ASC";
        \Yii::app()->debugLogger->message("Getting tickets by jql=$jql");

        $tickets = $jira->getTicketsByJql($jql);



        $branches = [];
        foreach ($tickets['issues'] as $ticket) {
            \Yii::app()->debugLogger->debug("Testing {$ticket['key']}");
            if (!\JiraFeature::model()->countByAttributes(['jf_ticket' => $ticket['key']])) {
                \Yii::app()->debugLogger->debug("Skip {$ticket['key']} as it is not known");
                //continue;
            }

            $branches[] = "feature/{$ticket['key']}";
        }

        \Yii::app()->debugLogger->message("Branches: ".implode(", ", $branches));

        if (!$branches) {
            \Yii::app()->debugLogger->message("No branches, se just create $branch from master branch");
            $model->sendMergeCreateBranch(new Message\Merge\CreateBranch($branch, "master", true));
            return;
        }


        $build = new \GitBuild();
        $build->branch = "tmp/build_".uniqid();
        $build->status = \GitBuild::STATUS_NEW;
        $build->user = $user;
        $build->additional_data = $branch;
        $build->save();

        \Yii::app()->debugLogger->message("Target branch: $build->branch, sending create branch task");

        $model->sendMergeCreateBranch(new Message\Merge\CreateBranch($build->branch, "master", false));
        $model->sendMergeTask(new Message\Merge\Task(-1, "master", $build->branch, Message\Merge\Task::MERGE_TYPE_FEATURE));

        foreach ($branches as $val) {

            if (!\GitBuildBranch::model()->countByAttributes([
                'git_build_id' => $build->obj_id,
                'branch' => $val,
            ])) {
                $b = new \GitBuildBranch();
                $b->git_build_id = $build->obj_id;
                $b->branch = $val;
                $b->status = \GitBuildBranch::STATUS_NEW;
                $b->errors = '[]';
                $b->save();
            }


            \Yii::app()->debugLogger->message("Branch: $val, sending merge branch task");
            $model->sendMergeTask(new Message\Merge\Task($build->obj_id, $val, $build->branch, Message\Merge\Task::MERGE_TYPE_BUILD));
        }

        \Log::createLogMessage("Заказана пересборка #$build->obj_id ветки $branch");
        \Yii::app()->webSockets->send('gitBuildRefreshAll', []);

        \Yii::app()->debugLogger->message("Tasks sent successfully");
    }
}