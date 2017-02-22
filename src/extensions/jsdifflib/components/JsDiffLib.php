<?php

namespace app\extensions\jsdifflib\components;

use app\extensions\jsdifflib\JsDiffLibAsset;

class JsDiffLib extends \yii\base\Component
{
    /**
     * @param \yii\web\View $view
    */
    public function register(\yii\web\View $view)
    {
        JsDiffLibAsset::register($view);
    }
}
