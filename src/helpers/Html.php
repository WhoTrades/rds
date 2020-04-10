<?php

namespace whotrades\rds\helpers;

class Html extends \yii\helpers\Html
{
    /**
     * @param string $url
     * @param string | null $text
     *
     * @return string
     */
    public static function aTargetBlank($url, $text = null) {
        $text = $text ?? $url;

        $anchorIcon = "<img src='/images/open_new_window.png' alt='Open new window' style='margin-left:5px; margin-bottom:3px; width:13px;height:13px;'>";

        return self::a(
            $text . $anchorIcon,
            $url,
            [
                'target' => '_blank',
                'onclick' => "window.open(this.href,'_blank');return false;",
            ]
        );
    }
}