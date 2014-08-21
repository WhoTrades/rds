<?php
class JsDiffLib extends CApplicationComponent
{
	public $forceCopyAssets = true;

	protected $_assetsUrl;

    public function init()
    {

    }

	/**
	 * Registers all Bootstrap CSS.
	 * @since 2.0.0
	 */
	public function registerAllCss()
	{
		Yii::app()->clientScript->registerCssFile($this->getAssetsUrl().'/css/diffview.css');
	}

	/**
	 * Registers the Bootstrap JavaScript.
	 * @param int $position the position of the JavaScript code.
	 */
	protected function registerJS($position = CClientScript::POS_HEAD)
	{
		/** @var CClientScript $cs */
		$cs = Yii::app()->getClientScript();
		$cs->registerCoreScript('jquery');
		$cs->registerScriptFile($this->getAssetsUrl().'/js/difflib.js', $position);
		$cs->registerScriptFile($this->getAssetsUrl().'/js/diffview.js', $position);
		$cs->registerScriptFile($this->getAssetsUrl().'/js/jquery.scrollTo.js', $position);
	}

	/**
	 * Registers all Bootstrap CSS and JavaScript.
	 * @since 2.1.0
	 */
	public function register()
	{
		$this->registerAllCss();
		$this->registerJS();
	}

	/**
	* Returns the URL to the published assets folder.
	* @return string the URL
	*/
	protected function getAssetsUrl()
	{
		if (isset($this->_assetsUrl))
			return $this->_assetsUrl;
		else
		{
			$assetsPath = Yii::getPathOfAlias('jsdifflib.assets');
			$assetsUrl = Yii::app()->assetManager->publish($assetsPath, true, -1, $this->forceCopyAssets);
			return $this->_assetsUrl = $assetsUrl;
		}
	}
}
