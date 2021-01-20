<?php

namespace whotrades\rds\helpers;

use yii\helpers\FileHelper;
use yii\i18n\PhpMessageSource;

class Translations
{

    public static function getAvailableLocales(): array
    {
        $messageSource = \Yii::$app->getI18n()->getMessageSource('rds*');
        if ($messageSource instanceof PhpMessageSource) {
            $locales = array_map('basename', FileHelper::findDirectories(\Yii::getAlias($messageSource->basePath), [
                'recursive' => false,
            ]));
            return array_combine($locales, $locales);
        }
        return [];
    }

}