<?php

namespace whotrades\rds\assets;

use yii\web\AssetBundle;

class I18NextHttpBackendAsset extends AssetBundle
{
    public $sourcePath = "@npm/i18next-http-backend";

    public $js = [
        'i18nextHttpBackend.js',
    ];

    public $depends = [
        'whotrades\rds\assets\I18NextAsset',
    ];
}
