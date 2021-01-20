<?php

namespace whotrades\rds\assets;

use Yii;
use yii\web\AssetBundle;

class I18NextICUAsset extends AssetBundle
{
    public $sourcePath = "@npm/i18next-icu";

    public $js = [
        'i18nextICU.js',
    ];

    public $depends = [
        'whotrades\rds\assets\I18NextAsset',
    ];

    public function init()
    {
        $this->js[] = 'locale-data/' . explode('-', Yii::$app->language, 2)[0] . '.js';
        parent::init();
    }
}
