<?php
/**
 * Модуль для работы pdev. Занимается переключением веток на контуре
 * @author Artem Naumenko
 */

class PDevModule extends CWebModule
{
    /**
     * @author Artem Naumenko
     */
    public function init()
    {
        $this->setImport(array(
            'pdev.controllers.*',
        ));
    }
}
