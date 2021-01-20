<?php
namespace whotrades\rds\models\User;

use whotrades\rds\helpers\Translations;

/**
 * Class Profile
 * @package whotrades\rds\models\User
 * @property string $locale
 */
class Profile extends \dektrium\user\models\Profile
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'rds.profile';
    }

    public function rules()
    {
        $rules = parent::rules();
        $rules['locale'] = ['locale', 'validateLocale'];

        return $rules;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels['locale'] = \Yii::t('rds', 'locale');
        return $labels;
    }

    public function validateLocale($attribute, $params)
    {
        $locales = Translations::getAvailableLocales();
        if (!in_array($this->$attribute, $locales)) {
            $this->addError($attribute, \Yii::t('rds/errors', 'wrong_locale_error'));
        }
    }
}
