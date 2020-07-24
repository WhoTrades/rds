<?php
/**
 * @author Maksim Rodikov
 */
namespace whotrades\rds\assets;

use \yii\web\AssetBundle;

class TimeJsAsset extends AssetBundle
{
    //public $basePath = '@webroot';
    //public $baseUrl = "@web";
    public $css = [];
    public $js = [
        '//cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.30/dayjs.min.js',
        '//cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.30/plugin/localizedFormat.min.js',
        '//cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.30/plugin/utc.min.js',
    ];
    public $locale;

    public function init()
    {
        parent::init();
        $locale = strtolower(\Yii::$app->language);
        $localeSegments = explode('-', $locale, 2);

        // mr: "short" locale version: en-EN => en, ru-RU => ru, e.t.c
        if ( 2 == count($localeSegments) && $localeSegments[0] == $localeSegments[1] ) {
            $locale = $localeSegments[0];
        }
        $this->js[] = "//cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.30/locale/{$locale}.min.js";
        $this->locale = $locale;
    }

}
