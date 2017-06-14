<?php

namespace app;

/**
 * @since #WTA-1149
 * @author Artem Rasskosov
 */

interface IHaveNavInterface
{
    /**
     * @param int $controllerId
     * @return array
     */
    public static function getNav($controllerId) : array;
}
