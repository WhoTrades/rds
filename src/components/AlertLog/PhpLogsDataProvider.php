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
     * @var AlertData[]
     */
    private $data;

    /**
     * @var \ServiceBase_IDebugLogger
     */
    private $debugLogger;

    /**
     * @param $debugLogger
     */
    public function __construct($debugLogger)
    {
        $this->debugLogger = $debugLogger;
    }

    /**
     * Название провайдера
     *
     * @return string
     */
    public function getName()
    {
        return 'PhpLogs';
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

            $host = parse_url(\Config::getInstance()->phpLogsSystem['service']['location'], PHP_URL_HOST);
            $httpSender = new \ServiceBase\HttpRequest\RequestSender($this->debugLogger);
            $url = "http://$host/status/list";
            $json = $httpSender->getRequest($url, ['format' => 'json'], self::TIMEOUT);
            $data = json_decode($json, true);

            if (!$data) {
                $this->debugLogger->error("Invalid json received from $url");
                return;
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
}