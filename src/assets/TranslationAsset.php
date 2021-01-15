<?php
namespace whotrades\rds\assets;

use Yii;
use yii\i18n\PhpMessageSource;
use yii\web\AssetBundle;

class TranslationAsset extends AssetBundle
{

    public $sourcePath = '@app/translations';

    public $depends = [
        'whotrades\rds\assets\I18NextICUAsset',
        'whotrades\rds\assets\I18NextHttpBackendAsset',
    ];

    /**
     * @return array<string>
     */
    public function getTranslationNamespaces(): array
    {
        $namespaces = [];

        foreach (Yii::$app->getI18n()->translations as $messageSource) {
            if ($messageSource instanceof PhpMessageSource && !empty($messageSource->fileMap)) {
                $namespaces = array_merge($namespaces, array_map(function ($ns) {
                    return str_replace('/', '_', $ns);
                }, array_keys($messageSource->fileMap)));
            }
        }
        $namespaces = array_unique($namespaces);

        return $namespaces;
    }
}
