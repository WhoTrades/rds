<?php
/**
 *
 * PHP version 5.4
 *
 * @author Dmitry Glizhinskiy <dg@whotrades.org>
 * @copyright © 2015 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */


namespace AlertLog;


use CompanyInfrastructure\TeamCityClient;

class TeamCityDataProvider implements IAlertDataProvider
{
    /**
     * @var AlertData[]
     */
    private $data;

    /**
     * @var \ServiceBase_IDebugLogger
     */
    private $debugLogger;

    /**
     * @var TeamCityClient
     */
    private $teamCityClient;

    /**
     * @var string
     */
    private $projectId;

    /**
     * @param \ServiceBase_IDebugLogger $debugLogger
     * @param TeamCityClient $teamCityClient
     * @param string $projectId Идентификатор проекта в TeamCity
     */
    public function __construct(\ServiceBase_IDebugLogger $debugLogger, \CompanyInfrastructure\WtTeamCityClient $teamCityClient, $projectId = 'Whotrades')
    {
        $this->debugLogger = $debugLogger;
        $this->teamCityClient = $teamCityClient;
        $this->projectId = $projectId;
    }

    /**
     * Название провайдера
     *
     * @return string
     */
    public function getName()
    {
        return 'TeamCity';
    }

    /**
     * @return AlertData[]
     */
    public function getData()
    {
        $this->loadDataIfNeeded();

        return $this->data;
    }

    /**
     * Загружает данные из TeamCity
     */
    private function loadDataIfNeeded()
    {
        if($this->data === null) {
            $this->data = [];

            $project = $this->teamCityClient->getProject($this->projectId);

            foreach ($project->buildTypes->children() as $buildType) {
                $alertData = $this->getAlertData($buildType);
                if ($alertData) {
                    $this->data[] = $alertData;
                }
            }
        }
    }

    /**
     * @param \SimpleXMLElement $buildType
     *
     * @return AlertData
     */
    private function getAlertData(\SimpleXMLElement $buildType)
    {
        $buildTypeId = $buildType->attributes()['id'];

        $build = $this->teamCityClient->getLastBuildByBuildType($buildTypeId);

        $alertName  = (string) $build->buildType->attributes()['projectName'] . ' :: ' . (string) $build->buildType->attributes()['name'];
        $url = (string) $build->attributes()['webUrl'];
        $status = (string) $build->attributes()['status'];

        if ($status === 'UNKNOWN') {
            $this->debugLogger->warning('process=getTeamCityBuildData, status=skip, reason=unknown_status');
            return null;
        }

        return new AlertData(
            $alertName,
            $status === 'SUCCESS' ? AlertData::STATUS_OK : AlertData::STATUS_ERROR,
            "url: $url"
        );
    }
}
