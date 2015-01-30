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

            throw $e;
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

            throw $e;
        }
    }

    public function getTicketInfo($ticket)
    {
        return $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket", 'GET', ['expand' => 'transitions,changelog']);
    }

    public function updateTicketTransition($ticket, $transitionId, $comment = null)
    {
        $request = ['transition' => ["id" => $transitionId]];
        if ($comment) {
            $request['update'] = ['comment' => [['add' => ['body' => $comment]]]];
        }
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

    public function deleteComment($ticket, $commentId)
    {
        $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/comment/$commentId", 'DELETE', []);
    }

    /**
     * Метод смотрит последний комментарий в тикете и смотрит его владельца. И в зависимости от того является ли автором RDS либо изменяет комментарий, либо добавляет новый
     *
     * @param string $ticket
     * @param string $text
     */
    public function addCommentOrAppendMyComment($ticket, $text)
    {
        $lastComment = end($this->getTicketInfo($ticket)['fields']['comment']['comments']);
        if ($lastComment['author']['name'] == $this->getUserName()) {
            $this->debugLogger->debug("Updating last comment {$lastComment['self']}");
            $this->updateComment($ticket, $lastComment['id'], $lastComment['body']."\n".$text);
        } else {
            $this->debugLogger->debug("Adding new comment with text=$text");
            $this->addComment($ticket, $text);
        }
    }

    /**
     * Метод смотрит последний комментарий в тикете и смотрит его владельца. И в зависимости от того является ли автором RDS либо изменяет комментарий, либо добавляет новый
     *
     * @param string $ticket
     * @param string $text
     */
    public function addCommentOrModifyMyComment($ticket, $text)
    {
        $lastComment = end($this->getTicketInfo($ticket)['fields']['comment']['comments']);
        if ($lastComment['author']['name'] == $this->getUserName()) {
            if (str_replace("\r", "", $lastComment['body']) != str_replace("\r", "", $text)) {
                $this->debugLogger->debug("Updating last comment {$lastComment['self']}");
                $this->updateComment($ticket, $lastComment['id'], $text);
            } else {
                $this->debugLogger->debug("Skip updating last comment {$lastComment['self']}, as it is not modified");
            }
        } else {
            $this->debugLogger->debug("Adding new comment with text=$text");
            $this->addComment($ticket, $text);
        }
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

    public function getTicketsByStatus($status)
    {
        return $this->getTicketsByJql("status=\"$status\"");
    }

    public function getTicketsByJql($jql)
    {
        return $this->sendRequest("$this->jiraUrl/rest/api/latest/search", 'GET', ['jql' => $jql, 'expand' => 'transitions,changelog']);
    }

    public function getTicketsByVersion($version)
    {
        $version= preg_replace('~[^\w.-]~', '', $version);
        $jql = "fixVersion = $version";

        return $this->getTicketsByJql($jql);
    }

    public function assign($ticket, $email)
    {
        $request = ['name' => static::getUserNameByEmail($email)];
        $this->sendRequest("$this->jiraUrl/rest/api/latest/issue/$ticket/assignee", 'PUT', $request);
        $info = $this->getTicketInfo($ticket);

        if ($info['fields']['assignee']['emailAddress'] != strtolower($email)) {
            throw new ApplicationException("Can't change email to $email for ticket $ticket");
        }
    }

    /**
     * Метод двигает задачу по статусам, на основании наших транзишенов
     *
     * @param array $ticketInfo - информация о тикете, возвращаемая методом getTicketInfo()
     * @param string $transition - элемент из констант класса Jira\Transition, например Jira\Transition::START_PROGRESS
     */
    public function transitionTicket($ticketInfo, $transition, $comment = null, $ignoreIncorrectStatus = false)
    {
        if (!isset(Jira\Transition::$transitionMap[$transition])) {
            throw new ApplicationException("Unknown tramsition '$transition'");
        }

        list($from, $to) = Jira\Transition::$transitionMap[$transition];

        if ($ticketInfo["fields"]["status"]["name"] != $from) {
            $this->debugLogger->message("Ignore ticket status: $ignoreIncorrectStatus");
            if ($ignoreIncorrectStatus) {
                $this->debugLogger->error("Can't apply transition '$transition', because ticket is in {$ticketInfo["fields"]["status"]["name"]} status, but $from needed, skip exception");
                return;
            }
            throw new ApplicationException("Can't apply transition '$transition', because ticket is in {$ticketInfo["fields"]["status"]["name"]} status, but $from needed");
        }

        $transitionId = null;
        $availableStatuses = [];
        foreach ($ticketInfo['transitions'] as $transitionItem) {
            $availableStatuses[] = $transitionItem['to']['name'];
            if ($transitionItem['to']['name'] == $to) {
                $transitionId = $transitionItem['id'];
            }
        }

        if (empty($transitionId)) {
            throw new ApplicationException("Can't apply transition $transition to ticket {$ticketInfo['key']}, despite the fact status of ticket=$from and is correct. Check JIRA roles for this project. Available statuses: ".implode(", ", $availableStatuses));
        }

        $this->updateTicketTransition($ticketInfo['key'], $transitionId, $comment);
    }

    public function getLastDeveloperNotRds($ticketInfo)
    {
        foreach (array_reverse($ticketInfo['changelog']['histories']) as $val) {
            foreach ($val['items'] as $item) {
                if ($item['field'] != 'assignee') {
                    continue;
                }
                if (strtolower($item['to']) == 'rds' && $item['from'] != '') {
                    return $item['from'].'@corp.finam.ru';
                }
            }
        }

        return null;
    }

    public static function getUserNameByEmail($email)
    {
        return preg_replace('~@.*~', '', $email);
    }
}
