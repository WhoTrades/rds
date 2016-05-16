<?php
/**
 * @package rds\zoho
 */

/**
 * Консьюмер, который двигает статусы тикетов из Ready for deploy -> Ready for acceptance в случае выкатывания релиза, и обратно в случае откатывания
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=ZohoAsync \
   --queue-name=rds_zoho_integration --consumer-name=rds_zoho_integration_consumer --partition=1 --dsn-name=DSN_DB4 --strategy=simple+retry -vvv \
   process_queue
 */

class PgQ_EventProcessor_ZohoAsync extends RdsEventProcessorBase
{
    const ZOHO_TIMEOUT = 30;
    /**
     * @param \PgQ\Event $event
     *
     * @throws CHttpException
     * @throws \CompanyInfrastructure\Exception\Jira\TicketNotFound
     * @throws \ServiceBase\HttpRequest\Exception\ResponseCode
     */
    public function processEvent(PgQ\Event $event)
    {
        $data = $event->getData();

        $task = $data['task'];
        $this->debugLogger->message("Processing event id={$event->getId()}, data = " . json_encode($event->getData(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        switch ($task) {
            case PgqZohoIntegration::TASK_NAME_SYNC_ZOHO_TITLE:
                $jiraIssueKey = json_decode($data['data'], true)['jiraIssueKey'];
                $this->debugLogger->message("Processing zoho sync title event, jira ticket=$jiraIssueKey");

                $requestSender = new \ServiceBase\HttpRequest\RequestSender($this->debugLogger);

                $jira = new \JiraApi(Yii::app()->debugLogger);
                $ticketInfo = $jira->getTicketInfo($jiraIssueKey);

                if (!$requestId = $this->parseZohoRequestId($ticketInfo)) {
                    throw new ApplicationException("Cant find requestId for ticket=$jiraIssueKey");
                }

                $params = [
                    'authtoken'     => '3b2055c77fe69fe7dcd9856bc3fd717e',
                    'portal'        => 'Just2Trade',
                    'department'    => 'Developers',
                    'searchfield'   => 'Request Id',
                    'searchvalue'   => $requestId,
                    'selectfields'  => 'Requests(caseId)',
                ];
                $url = "https://support.zoho.com/api/json/requests/getrecordsbysearch";
                $json = $requestSender->getRequest($url, $params, self::ZOHO_TIMEOUT);
                $this->debugLogger->message("Response: $json");
                $data = json_decode($json, true);
                if (!$data) {
                    $this->debugLogger->dump()->message('an', 'not_valid_json_received_from_zoho', true, [
                        'url' => $url,
                        'json' => $json,
                    ])->critical()->save();

                    if ($event->getRetry() < 1440 * 7) {
                        $this->debugLogger->error("Invalid json received from url=$url, retry event");
                        $event->retry(60);
                    } else {
                        $this->debugLogger->error("Invalid json received from url=$url, error occures more then 1 week, SKIP EVENT");
                    }

                    return;
                }

                $list = $data['response']['result']['Cases']['row']['fl'];
                $caseId = null;
                foreach ($list as $val) {
                    if ($val['val'] == 'CASEID') {
                        $caseId = $val['content'];
                    }
                }

                $params = [
                    'authtoken'     => '3b2055c77fe69fe7dcd9856bc3fd717e',
                    'portal'        => 'Just2Trade',
                    'department'    => 'Developers',
                    'id'            => $caseId,
                    'xml'           =>
                        '<requests><row no="1"><fl val="Subject">[JIRA] (' . $jiraIssueKey . ')' . htmlspecialchars($ticketInfo['fields']['summary']) . '</fl></row></requests>',
                ];
                $url = "https://support.zoho.com/api/json/requests/updaterecords";
                $json = $requestSender->getRequest($url, $params, self::ZOHO_TIMEOUT);
                $data = json_decode($json, true);
                $this->debugLogger->message("Response: $json");

                if (!$data) {
                    $this->debugLogger->dump()->message('an', 'not_valid_json_received_from_zoho', true, [
                        'url' => $url,
                        'json' => $json,
                    ])->critical()->save();

                    if ($event->getRetry() < 1440 * 7) {
                        $this->debugLogger->error("Invalid json received from url=$url, retry event");
                        $event->retry(60);
                    } else {
                        $this->debugLogger->error("Invalid json received from url=$url, error occures more then 1 week, SKIP EVENT");
                    }

                    return;
                }

                if ($data['response']['result']['responsedata']['Cases']['fl'][0]['content'] != 'Record(s) updated successfully') {
                    $this->debugLogger->dump()->message('an', 'not valid response received from zoho', true, [
                        'url' => $url,
                        '$json' => $json,
                        'data' => $data,
                    ])->critical()->save();

                    if ($event->getRetry() < 1440 * 7) {
                        $this->debugLogger->error("Invalid json received from url=$url, retry event");
                        $event->retry(60);
                    } else {
                        $this->debugLogger->error("Invalid json received from url=$url, error occures more then 1 week, SKIP EVENT");
                    }

                    return;
                }

                $this->debugLogger->message("Response OK: " . $data['response']['result']['responsedata']['Cases']['fl'][0]['content']);

                break;
            default:
                throw new \ApplicationException("Unknown task of zoho integration: '$task'");
                break;
        }
    }

    private function parseZohoRequestId($ticketInfo)
    {
        $summary = $ticketInfo['fields']['summary'];
        if (preg_match('~\[##(\d+)##\]~', $summary, $ans)) {
            return (int) $ans[1];
        }

        return null;
    }
}
