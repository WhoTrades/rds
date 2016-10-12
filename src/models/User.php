<?php
namespace app\models;

use app\modules\SingleLogin\components\SingleLoginAuth;

class User extends \yii\base\Model
{
    /**
     * @param string $id
     *
     * @return SingleLoginAuth
     * @throws \Exception
     */
    public static function findIdentity($id)
    {
        $currentUser = \Yii::$app->session->get('currentUser');
        if (!$currentUser || $id != $currentUser->getId()) {
            return null;
        }

        return $currentUser;
    }
}
