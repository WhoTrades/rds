<?php
/**
 * Class WtflowModule
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
