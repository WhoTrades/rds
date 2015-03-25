<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=GitDropFeatureBranch -vv
 */

use RdsSystem\Message;

class Cronjob_Tool_GitDropFeatureBranch extends RdsSystem\Cron\RabbitDaemon
{
    const REMOVE_BRANCHES_INTERVAL = '1 week';

    public static function getCommandLineSpec()
    {
        return [
            'dry-run' => [
                'desc' => 'Do nothing, only show branches to delete',
                'default' => false,
            ],
        ] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        if (!\Config::getInstance()->serviceRds['jira']['mergeTasks']) {
            $this->debugLogger->message("Tool disabled by config");
            return;
        }

        $jira = new JiraApi($this->debugLogger);
        $model = $this->getMessagingModel($cronJob);

        $features = JiraFeature::model()->findAllByAttributes([
            'jf_status' => JiraFeature::STATUS_CLOSED,
        ]);

        $this->debugLogger->message("Found ".count($features)." to test");

        foreach ($features as $feature) {
            $delete = false;
            /** @var $feature JiraFeature */
            $this->debugLogger->debug("Processing $feature->jf_branch");
            $info = [];
            try {
                $info = $jira->getTicketInfo($feature->jf_ticket);
            } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
                if ($e->getHttpCode() == 404 && $e->getResponse() == '{"errorMessages":["Issue Does Not Exist"],"errors":{}}') {
                    $this->debugLogger->message("Ticket was removed, removing branches");
                    $delete = true;
                } else {
                    throw $e;
                }
            }
            if (!$delete && $info['fields']['status']['name'] != \Jira\Status::STATUS_CLOSED) {
                $this->debugLogger->debug("Skip ticket as it is not closed");
                continue;
            }

            if (!$delete) {
                foreach($info['changelog']['histories'] as $val) {
                    foreach ($val['items'] as $item) {
                        if ($item['field'] != 'status' || $item['toString'] != 'Closed') {
                            continue;
                        }
                        $dateClosed = $val['created'];
                        if (time() > strtotime("$dateClosed +".self::REMOVE_BRANCHES_INTERVAL)) {
                            $this->debugLogger->message("Ticket was closed for old time, removing branches");
                            $delete = true;
                        } else {
                            $this->debugLogger->debug("Skip ticket as it is closed not more then ".self::REMOVE_BRANCHES_INTERVAL." ago");
                            continue 2;
                        }
                    }
                }
            }

            if ($delete) {
                if ($cronJob->getOption('dry-run')) {
                    $this->debugLogger->message("Fake remove branches $feature->jf_branch");
                } else {
                    $this->debugLogger->message("Removing branches $feature->jf_branch");
                    $c = new CDbCriteria();
                    $c->compare("jf_branch", $feature->jf_branch);
                    $c->compare('jf_status', JiraFeature::STATUS_CLOSED);
                    JiraFeature::model()->updateAll(['jf_status' => JiraFeature::STATUS_REMOVING], $c);
                    $model->sendDropBranches(new Message\Merge\DropBranches($feature->jf_branch));
                }
            }
        }
    }
}

