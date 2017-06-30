<?php
namespace Action\Git;

use app\models\Log;
use RdsSystem\Message;
use app\modules\Wtflow\models\GitBuild;
use app\modules\Wtflow\models\JiraFeature;
use app\modules\Wtflow\components\JiraApi;
use app\modules\Wtflow\models\GitBuildBranch;

class RebuildBranch
{
    private $map = [
        'develop' => [
            \CompanyInfrastructure\Jira\Status::STATUS_CODE_REVIEW,
            \CompanyInfrastructure\Jira\Status::STATUS_MERGE_TO_DEVELOP,

            \CompanyInfrastructure\Jira\Status::STATUS_MERGE_TO_STAGING,
            \CompanyInfrastructure\Jira\Status::STATUS_READY_FOR_CHECK,
            \CompanyInfrastructure\Jira\Status::STATUS_CHECKING,
            \CompanyInfrastructure\Jira\Status::STATUS_READY_FOR_STAGING,
            \CompanyInfrastructure\Jira\Status::STATUS_PACKED_FOR_RELEASE,
        ],
        'staging' => [
            \CompanyInfrastructure\Jira\Status::STATUS_MERGE_TO_STAGING,
            \CompanyInfrastructure\Jira\Status::STATUS_READY_FOR_CHECK,
            \CompanyInfrastructure\Jira\Status::STATUS_CHECKING,

            \CompanyInfrastructure\Jira\Status::STATUS_PACKED_FOR_RELEASE,
        ],
    ];
    /**
     * @param string $branch - ветка, которую нужно пересобрать, develop|staging
     * @param string $user - имя пользовеля, инициатора пересборки
     * @param \RdsSystem\Model\Rabbit\MessagingRdsMs $model
     * @param bool $checkForDuplicateTasks - проверять ли на повторную пересборку той же ветки
     * @throws \ApplicationException
     * @throws \Exception
     */
    public function run($branch, $user, \RdsSystem\Model\Rabbit\MessagingRdsMs $model, $checkForDuplicateTasks = null)
    {
        $checkForDuplicateTasks = $checkForDuplicateTasks === null ? true : false;

        if (!in_array($branch, ['develop', 'staging'])) {
            throw new \ApplicationException('Branch must be develop or staging only', 404);
        }

        if ($checkForDuplicateTasks) {
            $notReadyBranchesCount = GitBuild::countByAttributes([
                'additional_data' => $branch,
                'status' => GitBuild::STATUS_NEW,
            ]);

            if ($notReadyBranchesCount > 0) {
                throw new \ApplicationException(
                    "Ветка $branch уже пересобирается, дождитесь окончания процесса прежде чем пересобирать заново",
                    500
                );
            }
        }

        $branches = $this->getBranchesForBuild($branch);

        if (!$branches) {
            \Yii::$app->debugLogger->message("No branches, se just create $branch from master branch");
            $model->sendMergeCreateBranch(
                \Yii::$app->modules['Wtflow']->workerName,
                new Message\Merge\CreateBranch($branch, "master", true)
            );

            return;
        }

        $build = $this->createGitBuild($user, $branch);

        \Yii::$app->debugLogger->message("Target branch: $build->branch, sending create branch task");

        $model->sendMergeTask(
            \Yii::$app->modules['Wtflow']->workerName,
            new Message\Merge\Task(-1, "master", $build->branch, Message\Merge\Task::MERGE_TYPE_FEATURE)
        );

        foreach ($branches as $val) {
            if (!GitBuildBranch::countByAttributes([
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


            \Yii::$app->debugLogger->message("Branch: $val, sending merge branch task");
            $model->sendMergeTask(
                \Yii::$app->modules['Wtflow']->workerName,
                new Message\Merge\Task($build->obj_id, $val, $build->branch, Message\Merge\Task::MERGE_TYPE_BUILD)
            );
        }

        Log::createLogMessage("Заказана пересборка #$build->obj_id ветки $branch");
        \Yii::$app->webSockets->send('gitBuildRefreshAll', []);

        \Yii::$app->debugLogger->message("Tasks sent successfully");
    }

    /**
     * @param string $user
     * @param string $branch
     * @return GitBuild
     */
    private function createGitBuild($user, $branch)
    {
        $build = new GitBuild();
        $build->branch = "tmp/build_" . uniqid();
        $build->status = GitBuild::STATUS_NEW;
        $build->user = $user;
        $build->additional_data = $branch;

        $build->save();

        return $build;
    }

    /**
     * @param string $branch
     * @return string[]
     */
    private function getBranchesForBuild($branch)
    {
        $jira = \Yii::$app->getModule('Wtflow')->jira;

        $jql = "status IN (\"" . implode('", "', $this->map[$branch]) . "\") AND project IN (" . implode(", ", \Yii::$app->params['jiraProjects']) . ") ORDER BY updated ASC";
        \Yii::$app->debugLogger->message("Getting tickets by jql=$jql");

        $tickets = $jira->getTicketsByJql($jql);

        $branches = [];
        foreach ($tickets['issues'] as $ticket) {
            \Yii::$app->debugLogger->debug("Testing {$ticket['key']}");
            if (!JiraFeature::countByAttributes(['jf_ticket' => $ticket['key']])) {
                \Yii::$app->debugLogger->debug("Skip {$ticket['key']} as it is not known");
                //continue;
            }

            $branches[] = "feature/{$ticket['key']}";
        }

        \Yii::$app->debugLogger->message("Branches: " . implode(", ", $branches));

        return $branches;
    }
}
