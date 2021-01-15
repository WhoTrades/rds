<?php
declare(strict_types=1);

namespace whotrades\rds\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\FileHelper;
use yii\i18n\PhpMessageSource;

/**
 * Class TranslationController
 * @package whotrades\rds\commands
 */
class TranslationController extends Controller
{

    /**
     * Generates JSON translation dictionaries
     */
    public function actionGenerate()
    {
        Yii::info("Looking for translations to generate.");

        foreach (array_keys(Yii::$app->getI18n()->translations) as $categoryWildcard) {
            Yii::info("Checking {$categoryWildcard}");
            $messageSource = Yii::$app->getI18n()->getMessageSource($categoryWildcard);
            if (!($messageSource instanceof PhpMessageSource) || empty($messageSource->fileMap)) {
                continue;
            }

            $messages = [];
            $translationBasePath = Yii::getAlias($messageSource->basePath);
            $translationPublicPath = Yii::getAlias('@webroot/translations');

            foreach ($messageSource->fileMap as $category => $filename) {
                Yii::info("Converting {$category}:{$filename} at {$messageSource->basePath}");

                $translations = FileHelper::findFiles($translationBasePath, [
                    'only' => [$filename],
                ]);

                $category = str_replace('/', '_', $category);

                foreach ($translations as $translationPath) {
                    // extract locale
                    $locale = basename(dirname($translationPath));
                    // is a valid locale name
                    if (!preg_match('/^[a-z]{2}-[A-Z]{2}$/', $locale)) {
                        continue;
                    }
                    // TODO: Insecure
                    $translation = @include $translationPath;
                    if (!is_array($translation)) {
                        continue;
                    }

                    $messages[$locale][$category] = array_merge($messages[$locale][$category]??[], $translation);
                }
            }
            // Write to a file
            FileHelper::createDirectory($translationPublicPath);
            foreach ($messages as $locale => $categories) {
                foreach ($categories as $category => $messages) {
                    $jsonDictionaryPath = $translationPublicPath . '/' . $locale;
                    FileHelper::createDirectory($jsonDictionaryPath);
                    file_put_contents($jsonDictionaryPath . '/' . $category . '.json', json_encode($messages));
                }
            }

        }
    }
}
