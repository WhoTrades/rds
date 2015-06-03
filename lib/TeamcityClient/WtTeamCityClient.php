<?php
namespace TeamcityClient;

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../../../lib/pear/'); // vdm: HACK: путь к нашему pear в схеме app/tl
require_once 'HTTP/Request2.php';


class WtTeamCityClient extends TeamCityClient
{
    const DEFAULT_URL = 'http://rds:yz7119agyh@msa-build1.office.finam.ru/';

    public function __construct($serverUrl = self::DEFAULT_URL, $user = null, $password = null)
    {
        return parent::__construct($serverUrl, $user, $password);
    }

    public function createWtVcsRoot($repoName, $projectId = null)
    {
        $props = array(
            'agentCleanFilesPolicy' => 'ALL_UNTRACKED',
            'agentCleanPolicy' => 'ALWAYS',
            'authMethod' => 'PRIVATE_KEY_DEFAULT',
            'branch' => 'master',
            'ignoreKnownHosts' => true,
            'path' => '/srv/teamcity/git/' . $repoName,
            'submoduleCheckout' => 'IGNORE',
            'teamcity:branchSpec' => "+:refs/heads/(master)\n+:refs/heads/(release-*)",
            'url' => 'ssh://git.whotrades.net/srv/git/' . $repoName,
            'username' => 'teamcity',
            'usernameStyle' => 'NAME'
        );

        return $this->createVcsRoot($repoName, self::VCS_ROOT_TYPE_GIT, $props, $projectId);
    }

    public function getWtBuildTypeTemplate($projectName, $templateName)
    {
        $project = $this->getProjectByName($projectName);
        if (empty($project) || empty($project->templates)) {
            return null;
        }

        foreach ($project->templates[0] as $t) {
            if ((string)$t['name'] === $templateName) {
                return $this->getBuildType((string)$t['id']);
            }
        }

        return null;
    }
}


class TeamCityClient
{
    const VCS_ROOT_TYPE_GIT = 'jetbrains.git';


    private $client = null;
    private $basePath = '/httpAuth/app/rest';


    public function __construct($serverUrl, $user = null, $password = null)
    {
        $this->client = new \HTTP_Request2($serverUrl);

        if ($user != null && $password != null) {
            $this->client->setAuth($user, $password);
        }
    }

    /**********************************************     Builds        **************************************************/

    /**
     * @param $buildConfId
     * @param $branch
     * @param null $comment
     * @return \SimpleXMLElement
     */
    public function startBuild($buildConfId, $branch, $comment = null)
    {
        $body = '<build branchName="'.$branch.'">
        <buildType id="'.$buildConfId.'"/>
        <comment><text>'.htmlspecialchars($comment).'</text></comment>
        </build>
        ';
        return $result = $this->post('/buildQueue/', $body);
    }

    public function getBuildInfo($id)
    {
        $id = (int)$id;
        return $this->get('builds/id:'.$id);
    }

    public function getQueuedBuildInfo($queuedId)
    {
        $queuedId = (int)$queuedId;
        return $this->get('buildQueue/taskId:'.$queuedId);
    }


    /**********************************************     eof: Builds   **************************************************/

    /**********************************************     BuildTypes    **************************************************/
    public function attachBuildTypeToTemplate($buildTypeId, $templateId)
    {
        $this->put("/buildTypes/$buildTypeId/template", $templateId, array('Content-Type' => 'text/plain'));
    }

    public function attachVcsRootToBuildType($buildTypeId, $vcsRootId)
    {
        $body = "<vcs-root-entry><vcs-root id=\"$vcsRootId\"/></vcs-root-entry>";
        $this->post("/buildTypes/$buildTypeId/vcs-root-entries", $body);
    }

    public function copyBuildType($projectId, $name, $sourceBuildTypeLocator)
    {
        $body = "<newBuildTypeDescription name='$name' sourceBuildTypeLocator='id:$sourceBuildTypeLocator' />";
        return $this->post("/projects/$projectId/buildTypes", $body);
    }

    public function copyBuildTypeTemplate($projectId, $name, $sourceBuildTypeTemplateLocator)
    {
        $body = "<newBuildTypeDescription name='$name' sourceBuildTypeLocator='id:$sourceBuildTypeTemplateLocator' />";
        return $this->post("/projects/$projectId/templates", $body);
    }

    /**
     * Создает пустой BuildType.
     *
     * @param $projectId
     * @param $name
     *
     * @return \SimpleXMLElement
     */
    public function createBuildType($projectId, $name)
    {
        return $this->post("/projects/$projectId/buildTypes", $name, array('Content-Type' => 'text/plain'));
    }

    public function deleteBuildType($id)
    {
        $this->delete("/buildTypes/$id");
    }

    public function detachBuildTypeFromTemplate($id, $templateId)
    {
        $this->delete("/buildTypes/$id/template", $templateId);
    }

    public function getBuildType($id)
    {
        return $this->get('/buildTypes/' . $id);
    }

    public function getBuildTypeByName($projectId, $buildTypeName)
    {
        foreach ($this->getBuildTypesList() as $bt) {
            if (
                    strtolower((string)$bt['name']) === strtolower($buildTypeName)
                    && (string)$bt['projectId'] === $projectId
            ) {
                return $this->getBuildType((string)$bt['id']);
            }
        }

        return null;
    }

    public function getBuildTypesList()
    {
        return $this->get('/buildTypes');
    }
    /**********************************************     eof: BuildTypes    *********************************************/


    /**********************************************     Projects    ***************************************************/
    public function createProject($name)
    {
        return $this->post('/projects/', $name, array('Content-Type' => 'text/plain'));
    }

    public function deleteProject($id)
    {
        $this->delete('/projects/' . $id);
    }

    public function deleteProjectByMask($mask)
    {
        foreach ($this->getProjectsList() as $p) {
            if (preg_match('#' . $mask . '#', (string)$p['name'])) {
                $this->deleteProject((string)$p['id']);
            }
        }
    }

    public function getProject($id)
    {
        return $this->get('/projects/' . $id);
    }

    public function getProjectByName($name)
    {
        foreach ($this->getProjectsList() as $p) {
            if (strtolower((string)$p['name']) === strtolower($name)) {
                return $this->getProject((string)$p['id']);
            }
        }

        return null;
    }

    public function getProjectsList()
    {
        return $this->get('/projects');
    }
    /**********************************************    eof: Projects    ***********************************************/


    /**********************************************     VCS roots    **************************************************/
    public function createVcsRoot($name, $type, array $props, $projectId = null)
    {
        $body = $this->createXmlElement("<vcs-root name=\"$name\" vcsName=\"$type\"></vcs-root>");
        if (empty($projectId)) {
            $body->addAttribute('shared', true);
        }

        $propsNode = $body->addChild('properties');
        foreach ($props as $name => $val) {
            $node = $propsNode->addChild('property');
            $node->addAttribute('name', $name);
            $node->addAttribute('value', $val);
        }

        return $this->post('/vcs-roots', $body->saveXML());
    }

    public function getVcsRoot($id)
    {
        return $this->get('/vcs-roots/' . $id);
    }

    public function getVcsRootByName($name)
    {
        foreach ($this->getVcsRootsList() as $v) {
            if (strtolower((string)$v['name']) === strtolower($name)) {
                return $this->getVcsRoot((string)$v['id']);
            }
        }

        return null;
    }

    public function getVcsRootProperty($id, $propName)
    {
        return $this->get("/vcs-roots/$id/properties/$propName", array('Content-Type' => 'application/xml'), true);
    }

    public function getVcsRootsList()
    {
        return $this->get('/vcs-roots');
    }

    public function updateVcsRootProperty($id, $propName, $propValue)
    {
        return $this->put("/vcs-roots/$id/properties/$propName", $propValue, array('Content-Type' => 'text/plain'));
    }
    /**********************************************     eof: VCS roots    **********************************************/


    /**************************************************************************************************************/

    private function apiCall($relativePath, $method, array $headers, $body = '', $plainTextResponse = false)
    {
        // set headers
        foreach ($headers as $h => $v) {
            $this->client->setHeader($h, $v);
        }

        $this->client->setMethod($method);
        $this->client->getUrl()->setPath($this->basePath . '/'. trim($relativePath, '/'));

        if (!empty($body)) {
            $this->client->setBody($body);
        }

        $response = $this->client->send();

        // reset headers
        foreach ($headers as $h => $v) {
            $this->client->setHeader($h, null);
        }

        if ($response->getStatus() >= 400) {
            throw new \Exception($response->getBody(), $response->getStatus());
        }

        return $plainTextResponse ? $response->getBody() : $this->createXmlElement($response->getBody());
    }

    public function getBuildsByBranch($branch, $count = 30)
    {
        return $this->get("/builds/?count=10000&locator=branch:$branch");
    }

    private function get($resource, array $headers = array('Content-Type' => 'application/xml'), $plainTextResponse = false)
    {
        return $this->apiCall($resource, \HTTP_Request2::METHOD_GET, $headers, '', $plainTextResponse);
    }

    private function post($resource, $body, array $headers = array('Content-Type' => 'application/xml'))
    {
        return $this->apiCall($resource, \HTTP_Request2::METHOD_POST, $headers, $body);
    }

    private function put($resource, $body, array $headers = array('Content-Type' => 'application/xml'))
    {
        return $this->apiCall($resource, \HTTP_Request2::METHOD_PUT, $headers, $body);
    }

    private function delete($resource, array $headers = array('Content-Type' => 'application/xml'))
    {
        return $this->apiCall($resource, \HTTP_Request2::METHOD_DELETE, $headers);
    }

    private function createXmlElement($source)
    {
        $xmlUseErrorsPrevious = libxml_use_internal_errors(true);

        $result = simplexml_load_string($source);
        if (empty($result)) {
            return null;
        }

        libxml_use_internal_errors($xmlUseErrorsPrevious);

        if (false === $result) {
            throw new \Exception(var_export(libxml_get_errors(), 1) . " Source [$source]");
        }


        return $result;
    }
}