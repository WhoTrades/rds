<?php
/**
 * @package rds\sentry
 */

/**
 * @author Artem Naumenko
 */
class SentryModule extends CWebModule
{
    /**
     * @author Artem Naumenko
     */
    public function init()
    {
        die('1111');
        $this->setImport(array(
            'Sentry.PgQ.EventProcessor.*',
        ));
    }
}
