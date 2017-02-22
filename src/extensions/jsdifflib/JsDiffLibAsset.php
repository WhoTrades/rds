<?php
namespace app\extensions\jsdifflib;

use yii\web\AssetBundle;

class JsDiffLibAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/assets/';
    public $css = ['css/diffview.css'];
    public $js = ['js/difflib.js', 'js/diffview.js', 'js/jquery.scrollTo.js'];
    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
