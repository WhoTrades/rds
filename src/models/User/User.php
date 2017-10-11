<?php
namespace whotrades\rds\models\User;

class User extends \dektrium\user\models\User
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'rds.user';
    }
}
