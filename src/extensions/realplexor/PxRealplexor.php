<?php
Yii::import('ext.realplexor.Dklab_Realplexor');
/**
 * Created by JetBrains PhpStorm.
 * User: vaseninm
 * Date: 23.10.12
 * Time: 15:49
 */
class PxRealplexor extends CApplicationComponent {

    public $host = '0.0.0.0';
    public $port = '10010';
    public $namespace = 'realplexor_';
    public $identifier = 'identifier';
    public $path = '/';

    public $server = NULL;

    public $login = NULL;
    public $password = NULL;

    /** @var Dklab_Realplexor */
    protected $_rpl = NULL;

    public function init() {
        if ($this->_rpl === NULL) {
            $this->_rpl = new Dklab_Realplexor($this->host, $this->port, $this->namespace, $this->identifier, $this->path);
        }
        if (!empty($this->login) && !empty($this->password)) {
            $this->_rpl->logon($this->login, $this->password);
        }
        parent::init();
    }

    public function send($idsAndCursors, $data, $showOnlyForIds = null) {
        return $this->_rpl->send($idsAndCursors, $data, $showOnlyForIds);
    }

    public function cmdOnlineWithCounters($idPrefixes = null) {
        return $this->_rpl->cmdOnlineWithCounters($idPrefixes);
    }

    public function cmdOnline($idPrefixes = null) {
        return $this->_rpl->cmdOnline($idPrefixes);
    }

    public function cmdWatch($fromPos, $idPrefixes = null) {
        return $this->_rpl->cmdWatch($fromPos, $idPrefixes);
    }

    public function registerScripts () {
        $assets=dirname(__FILE__).'/assets';
        $baseUrl=Yii::app()->assetManager->publish($assets);
        if(is_dir($assets)){
            Yii::app()->clientScript->registerScriptFile($baseUrl.'/dklab_realplexor.js',CClientScript::POS_HEAD);
            Yii::app()->clientScript->registerScriptFile($baseUrl.'/realplexor.js',CClientScript::POS_HEAD);
            Yii::app()->clientScript->registerScript('rplServer', '
            realplexor = new initRealplexors(
                "http://"+document.location.host+'.CJavaScript::encode($this->server).'
            );
            ', CClientScript::POS_BEGIN);
        } else {
            throw new Exception('Realplexor - Error: Couldn\'t find assets to publish.');
        }
    }
}
