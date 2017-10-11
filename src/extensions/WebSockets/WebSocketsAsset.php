<?php
namespace whotrades\rds\extensions\WebSockets;

use yii\web\AssetBundle;

class WebSocketsAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/assets/';

    public $jsOptions = ['position' => \yii\web\View::POS_BEGIN];

    public $js = ['autobahn.min.js'];
}
