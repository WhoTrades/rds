<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Jira_CreateProjectComponents -vv
 */
class Cronjob_Tool_Jira_CreateProjectComponents extends \Cronjob\Tool\ToolBase
{
    public static function getCommandLineSpec()
    {
        return [
            'jira-projects' => [
                'desc' => 'List of jira projects devided by comma (,)',
                'valueRequired' => true,
                'default' => null
            ],
        ];
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $jiraApi = new JiraApi($this->debugLogger);

        $jiraProjects = $cronJob->getOption('jira-projects')
            ? explode(",", $cronJob->getOption('jira-projects'))
            : Yii::app()->params['jiraProjects'];

        $projects = Project::model()->findAll();
        foreach ($jiraProjects as $jiraProject) {
            $this->debugLogger->message($jiraProject);
            $info = $jiraApi->getProjectInfo($jiraProject);
            foreach ($projects as $project) {
                foreach ($info['components'] as $component) {
                    if ($component['name'] == $project->project_name) {
                        continue 2;
                    }
                }

                $jiraApi->createComponent($project->project_name, $project->project_name.' [auto]', $jiraProject);
            }
        }
    }
}
