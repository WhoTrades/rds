<?php
/**
 *
 * PHP version 5.4
 *
 * @author Dmitry Glizhinskiy <dg@whotrades.org>
 * @copyright © 2015 WhoTrades, Ltd. (http://whotrades.com). All rights reserved.
 */

namespace app\components\AlertLog;

class AlertData
{
    const STATUS_OK    = 'ok';
    const STATUS_ERROR = 'error';

    private $name;
    private $status;
    private $text;

    /**
     * @param $name
     * @param $status
     * @param $text
     */
    public function __construct($name, $status, $text)
    {
        $this->name = $name;
        $this->status = $status;
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->getStatus() === self::STATUS_ERROR;
    }
}