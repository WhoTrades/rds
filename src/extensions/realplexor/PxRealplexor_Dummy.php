<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vaseninm
 * Date: 10.11.12
 * Time: 1:08
 * To change this template use File | Settings | File Templates.
 */
class PxRealplexor_Dummy extends PxRealplexor
{
    public function init()
    {
        return false;
    }

    public function send($idsAndCursors, $data, $showOnlyForIds = null)
    {
        return false;
    }

    public function cmdOnlineWithCounters($idPrefixes = null)
    {
        return array();
    }

    public function cmdOnline($idPrefixes = null)
    {
        return array();
    }

    public function cmdWatch($fromPos, $idPrefixes = null)
    {
        return array();
    }

    public function registerScripts()
    {
        return false;
    }

}