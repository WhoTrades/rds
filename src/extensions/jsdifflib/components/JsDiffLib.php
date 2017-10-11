<?php

namespace whotrades\rds\extensions\jsdifflib\components;

use whotrades\rds\extensions\jsdifflib\JsDiffLibAsset;

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
