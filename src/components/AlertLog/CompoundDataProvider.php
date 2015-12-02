<?php
/**
 *
 * PHP version 5.4
 *
 * @author Dmitry Glizhinskiy <dg@whotrades.org>
 * @copyright © 2015 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */


namespace AlertLog;


class CompoundDataProvider implements IAlertDataProvider
{
    /**
     * @var IAlertDataProvider[]
     */
    private $dataProviders = [];

    /**
     * @var \ServiceBase_IDebugLogger
     */
    private $debugLogger;

    /**
     * @var string Название провайдера
     */
    private $name;

    /**
     * @param \ServiceBase_IDebugLogger $debugLogger
     * @param string $name              Название провайдера
     * @param IAlertDataProvider[]      $dataProviders
     */
    public function __construct(\ServiceBase_IDebugLogger $debugLogger, $name, array $dataProviders)
    {
        $this->debugLogger = $debugLogger;
        $this->name = $name;
        $this->dataProviders = $dataProviders;
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
        $result = [];

        foreach ($this->dataProviders as $dataProvider) {
            $result = array_merge($result, $dataProvider->getData());
        }

        return $result;
    }
}