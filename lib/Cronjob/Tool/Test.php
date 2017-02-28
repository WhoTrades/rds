<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    /**
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     *
     * @throws CException
     * @throws phpmailerException
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $jira = new JiraApi($this->debugLogger);
        $tickets = $jira->getTicketsByStatus(CompanyInfrastructure\Jira\Status::STATUS_CODE_REVIEW, ['WTT']);

        $stashApi = new \CompanyInfrastructure\StashApi($this->debugLogger);

        foreach ($tickets['issues'] as $ticketInfo) {
            $ticket = $ticketInfo['key'];

            $pullRequests = $stashApi->getPullRequestsByBranch("WT", "sparta", "refs/heads/feature/$ticket");

            foreach ($pullRequests['values'] as $pullRequest) {
                var_export($pullRequest);

                //$jira->transitionTicket($ticketInfo, JIra\Transition::FAILED_CODE_REVIEW);
                //$jira->transitionTicket($ticketInfo, JIra\Transition::APPROVE_CODE_REVIEW);
                return;
            }
        }
    }
}
