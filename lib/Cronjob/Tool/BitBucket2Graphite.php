<?php
/**
 * Тул для сбора статистики из BitBucket и передачи ее в Graphite
 *      - количество открытых pull-request
 *
 * @example dev/services/rds/misc/tools/runner.php --tool=BitBucket2Graphite -vv --project=WT --repo=sparta
 * @author Dmitry Vorobyev
 */
class Cronjob_Tool_BitBucket2Graphite extends Cronjob\Tool\ToolBase
{
    /**
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [
            'project' => [
                'desc' => 'Project in Stash/BitBucket',
                'default' => 'WT',
                'valueRequired' => true,
                'required' => true,
            ],
            'repo' => [
                'desc' => 'Repo name',
                'default' => 'sparta',
                'valueRequired' => true,
                'required' => true,
            ],
        ];
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     *
     * @return int
     * @throws Exception
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $this->debugLogger->message("Hello World");
        $project = $cronJob->getOption('project');
        $repo = $cronJob->getOption('repo');

        $stash = new \CompanyInfrastructure\StashApi($this->debugLogger);
        $count = $stash->getPullRequestOpenCount($project, $repo);

        /* @var $graphite \GraphiteSystem\Graphite */
        $graphite = \Yii::$app->graphite->getGraphite();
        $graphite->gauge(\GraphiteSystem\Metrics::dynamicName(\GraphiteSystem\Metrics::GIT_PULL_REQUEST___GAUGE, [$project, $repo]), $count);

        return 0;
    }
}
