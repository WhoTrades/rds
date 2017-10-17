<?php

namespace whotrades\rds;

/**
 * @since #WTA-1149
 * @author Artem Rasskosov
 */

interface IHaveNavInterface
{
    /**
     * @param string $controllerId
     * @param string $actionId
     * @return array
     */
    public static function getNav($controllerId, $actionId) : array;
}
