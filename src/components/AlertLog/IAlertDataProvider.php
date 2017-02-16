<?php
/**
 *
 * PHP version 5.4
 *
 * @author Dmitry Glizhinskiy <dg@whotrades.org>
 * @copyright © 2015 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */

namespace app\components\AlertLog;

interface IAlertDataProvider
{
    /**
     * Название провайдера
     *
     * @return string
     */
    public function getName();

    /**
     * @return AlertData[]
     */
    public function getData();
}