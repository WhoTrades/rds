<?php
/**
 * Class ZohoController
 */

use \CompanyInfrastructure\Exception\Jira\TicketNotFound;
use \ServiceBase\HttpRequest\Exception\ResponseCode;

class ApiController extends Controller
{
    /**
     * @param string $issueKey
     *
     * @throws TicketNotFound
     * @throws ResponseCode
     */
    public function actionJiraTicketCreated($issueKey)
    {
        $model = new PgqZohoIntegration();
        $model->task = PgqZohoIntegration::TASK_NAME_SYNC_ZOHO_TITLE;
        $model->data = json_encode(['jiraIssueKey' => $issueKey]);
        echo json_encode(['ok' => $model->save(), 'error' => $model->getErrors()], JSON_UNESCAPED_UNICODE);
    }
}
