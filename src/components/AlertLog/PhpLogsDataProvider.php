<?php
/**
 *
 * PHP version 5.4
 *
 * @author Dmitry Glizhinskiy <dg@whotrades.org>
 * @copyright © 2015 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */

namespace AlertLog;

class PhpLogsDataProvider implements IAlertDataProvider
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
     * Загружает данные из PhpLogs
     */
    private function loadDataIfNeeded()
    {
        if($this->data === null) {
            $this->data = [];

            $httpSender = new \ServiceBase\HttpRequest\RequestSender($this->debugLogger);
            $url = $this->getDataProviderUrl();
            $json = $httpSender->getRequest($url, ['format' => 'json'], self::TIMEOUT);
            $data = json_decode($json, true);

            if (!$data) {
                $this->debugLogger->error("Invalid json received from $url");
                throw new BadJsonException();
            }

            foreach ($data['result']['data'] as $name => $val) {
                if (empty($val['data']) || (isset($val['data']['result']['data']) && empty($val['data']['result']['data']))) {
                    $status = \AlertLog::STATUS_OK;
                } else {
                    $status = \AlertLog::STATUS_ERROR;
                }
                $text = "url: {$val['url']}";

                $alertData = new AlertData($name, $status, $text);

                $this->data[] = $alertData;
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