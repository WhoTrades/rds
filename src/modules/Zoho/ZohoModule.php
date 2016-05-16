<?php
/**
 * @package rds\zoho
 */

/**
 * @author Artem Naumenko
 */
class ZohoModule extends CWebModule
{
    /**
     * @author Artem Naumenko
     */
    public function init()
    {
        $this->setImport(array(
            'Zoho.models.*',
            'Zoho.controllers.*',
            'Zoho.PgQ.EventProcessor.*',
        ));
    }
}
