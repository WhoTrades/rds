<?php
namespace app\models\User;

class Token extends \dektrium\user\models\Token
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'rds.token';
    }
}
