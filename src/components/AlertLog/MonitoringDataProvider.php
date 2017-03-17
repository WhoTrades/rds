<?php
/**
 *
 * PHP version 5.4
 *
 * @author Artem Naumenko <an@whotrades.org>
 * @copyright © 2016 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */

namespace app\components\AlertLog;

use app\models\AlertLog;

class MonitoringDataProvider implements IAlertDataProvider
{
    const TIMEOUT = 60;

    /**
     * @var string Название провайдера
     */
    protected $name;

    /**
     * @var string url источника данных
     */
    protected $dataProviderUrl;

    /**
     * @var AlertData[]
     */
    private $data;

    /**
     * @var \ServiceBase_IDebugLogger
     */
    private $debugLogger;

    /**
     * @param \ServiceBase_IDebugLogger $debugLogger
     * @param string $name название провайдера
     * @param string $dataProviderUrl url источника данных
     */
    public function __construct(\ServiceBase_IDebugLogger $debugLogger, $name, $dataProviderUrl)
    {
        $this->name = $name;
        $this->dataProviderUrl = $dataProviderUrl;
        $this->debugLogger = $debugLogger;
    }

    /**
     * Название провайдера
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Загружает данные из service-monitoring
     */
    private function loadDataIfNeeded()
    {
        if ($this->data === null) {
            $this->data = [];

            $httpSender = new \ServiceBase\HttpRequest\RequestSender($this->debugLogger);
            $url = $this->getDataProviderUrl();
            $json = $httpSender->getRequest($url, ['format' => 'json'], self::TIMEOUT);
            $data = json_decode($json, true);

            if (!$data) {
                $this->debugLogger->error("Invalid json received from $url");
                throw new BadJsonException();
            }

            foreach ($data as $group => $list) {
                foreach ($list as $name => $val) {
                    if (empty($val['checkResult'])) {
                        $status = AlertLog::STATUS_OK;
                    } else {
                        $status = AlertLog::STATUS_ERROR;
                    }
                    $text = "url: {$val['link']}";

                    $alertData = new AlertData($name, $status, $text);

                    $this->data[] = $alertData;
                }
            }
        }
    }

    /**
     * Возвращает url источника данных
     *
     * @return string
     */
    private function getDataProviderUrl()
    {
        return $this->dataProviderUrl;
    }
}
