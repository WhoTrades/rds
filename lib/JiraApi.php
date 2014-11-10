<?php
class JiraApi
{
    const DEFAULT_JIRA_URL = 'http://msk-bls1.office.finam.ru:9380';
    const DEFAULT_JIRA_USER_PASSWORD = 'RDS:yz7119agyh';

    const TIMEOUT = 30;

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

    public function getUserName()
    {
        return explode(":", $this->jiraUserPassword)[0];
    }

    private function sendRequest($url, $method, $request)
    {
        if (strtoupper($method) == 'GET') {
            $json = $this->httpSender->getRequest($url, $request, self::TIMEOUT, $this->globalCurlSettings);
        } else {
            $json = $this->httpSender->postRequest($url, json_encode($request), self::TIMEOUT,[CURLOPT_CUSTOMREQUEST => $method,] + $this->globalCurlSettings);
        }

        //an: пустой ответ - значит все хорошо
        if (empty($json)) {
            return true;
        }

        $data = json_decode($json, true);

        if ($data === null && $data != 'null') {
            \CoreLight::getInstance()->getServiceBaseDebugLogger()->dump()->message('an', 'invalid_json_received', true, ['json' => $json])->save();
            throw new ApplicationException('invalid_json_received');
        }

        if (!empty($data['errors'])) {
            \CoreLight::getInstance()->getServiceBaseDebugLogger()->dump()->message('an', 'jira_error_cant_create_version', true, ['data' => $data])->save();
            throw new ApplicationException('jira_error_cant_create_version');
        }

        return $data;
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

        $this->sendRequest("$this->jiraUrl/rest/api/latest/version/", 'POST', $request);
    }

    public function archiveProjectVersion($versionId, $archived = true)
    {
        $request = [
            "archived" => $archived,
        ];

        $this->sendRequest("$this->jiraUrl/rest/api/latest/version/$versionId", 'PUT', $request);
    }

    public function releaseProjectVersion($versionId, $released = true, $userReleaseDate  = null)
    {
        $request = [
            'released' => $released,
            'releaseDate' => date('Y-m-d H:i:s', $userReleaseDate ? strtotime($userReleaseDate) : time()),
        ];

        $this->sendRequest("$this->jiraUrl/rest/api/latest/version/$versionId", 'PUT', $request);
    }

    public function removeProjectVersion($versionId)
    {
        $this->sendRequest("$this->jiraUrl/rest/api/latest/version/$versionId", 'DELETE', '');
    }

    public function addTicketLabel($ticket, $label)
    {
        $request = [
            'update' => [
                'labels' => [
                    ['add' => $label],
                ],
            ],
        ];

        try {
            $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/", 'PUT', $request);
        } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            if ($e->getHttpCode() == 404) {
                //an: Такого тикета просто не существует
                return false;
            }
        }

        return true;
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

        try {
            $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/", 'PUT', $request);
        } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            if ($e->getHttpCode() == 404) {
                //an: Такого тикета просто не существует
                return false;
            }
        }
    }

    public function getTicketInfo($ticket)
    {
        return $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket", 'GET', ['expand' => 'transitions']);
    }

    public function updateTicketTransition($ticket, $transitionId)
    {
        $request = ['transition' => ["id" => $transitionId],];
        $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/transitions", 'POST', $request);
    }

    public function addComment($ticket, $text)
    {
        $request = ['body' => $text];
        $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/comment", 'POST', $request);
    }

    public function updateComment($ticket, $commentId, $text)
    {
        $request = ['body' => $text];
        $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/comment/$commentId", 'PUT', $request);
    }

    public function getAllVersions($project)
    {
        return $this->sendRequest("$this->jiraUrl/rest/api/latest/project/$project/versions", 'GET', []);
    }

    public function getTicketsByVersions($versions)
    {
        if (empty($versions)) return [];

        $versions = array_map(function($version){return preg_replace('~[^\w.-]~', '', $version);}, $versions);
        $jql = 'fixVersion IN ('.implode(", ", $versions).')';
        return $this->getTicketsByJql($jql);
    }

    public function getTicketsByJql($jql)
    {
        return $this->sendRequest("$this->jiraUrl/rest/api/latest/search", 'GET', ['jql' => $jql, 'expand' => 'transitions']);
    }

    public function getTicketsByVersion($version)
    {
        $version= preg_replace('~[^\w.-]~', '', $version);
        $jql = "fixVersion = $version";

        return $this->getTicketsByJql($jql);
    }
}
