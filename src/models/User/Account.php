<?php
namespace app\models\User;

class Account extends \dektrium\user\models\Account
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'rds.social_account';
    }
}
