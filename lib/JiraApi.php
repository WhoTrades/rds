<?php
class JiraApi
{
    const DEFAULT_JIRA_URL = 'http://msk-bls1.office.finam.ru:9380';
    const DEFAULT_JIRA_USER_PASSWORD = 'githook:githook';

    /** @var ServiceBase_IDebugLogger */
    private $debugLogger;

    /** @var \ServiceBase\HttpRequest\RequestSender */
    private $httpSender;

    private $jiraUrl;
    private $jiraUserPassword;

    private $globalCurlSettings;

    public function __construct(\ServiceBase_IDebugLogger $debugLogger, $jiraUrl = self::DEFAULT_JIRA_URL, $userPassword = self::DEFAULT_JIRA_USER_PASSWORD)
    {
        $this->debugLogger = $debugLogger;
        $this->httpSender = new \ServiceBase\HttpRequest\RequestSender($this->debugLogger);

        $this->jiraUrl = $jiraUrl;
        $this->jiraUserPassword = $userPassword;

        $this->globalCurlSettings = array(
            CURLOPT_USERPWD => $this->jiraUserPassword,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_FOLLOWLOCATION=> 0,
        );
    }

    public function createProjectVersion($project, $name, $description, $archived, $released)
    {
        $request = [
            'project' => $project,
            'description' => $description,
            'name' => $name,
            "archived" => $archived,
            "released" => $released,
        ];
        $json = $this->httpSender->postRequest("$this->jiraUrl/rest/api/latest/version/", json_encode($request), 10,
            [
                CURLOPT_CUSTOMREQUEST => 'POST',
            ] + $this->globalCurlSettings
        );

        $data = json_decode($json, true);

        //an: пустой ответ - значит все хорошо
        if (empty($data)) {
            return;
        }

        if (!$data) {
            \CoreLight::getInstance()->getServiceBaseDebugLogger()->dump()->message('an', 'invalid_json_received', true, ['json' => $json])->save();
            throw new ApplicationException('invalid_json_received');
        }

        if (!empty($data['errors'])) {
            \CoreLight::getInstance()->getServiceBaseDebugLogger()->dump()->message('an', 'jira_error_cant_create_version', true, ['data' => $data])->save();
            throw new ApplicationException('jira_error_cant_create_version');
        }
    }

    public function addTicketFixVersion($ticket, $fixVersion)
    {
        $request = [
            'update' => [
                'fixVersions' => [
                    ['add' => ["name" => $fixVersion]],
                ],
            ],
        ];

        $json = $this->httpSender->postRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/", json_encode($request), 10,
            [
                CURLOPT_CUSTOMREQUEST => 'PUT',
            ] + $this->globalCurlSettings
        );

        $data = json_decode($json, true);

        //an: пустой ответ - значит все хорошо
        if (empty($data)) {
            return;
        }

        if (!$data) {
            \CoreLight::getInstance()->getServiceBaseDebugLogger()->dump()->message('an', 'invalid_json_received', true, ['json' => $json])->save();
            throw new ApplicationException('invalid_json_received');
        }

        if (!empty($data['errors'])) {
            \CoreLight::getInstance()->getServiceBaseDebugLogger()->dump()->message('an', 'jira_error_cant_create_version', true, ['data' => $data])->save();
            throw new ApplicationException('jira_error_cant_create_version');
        }
    }
}
