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

    /**
     * @return \yii\rbac\Role[]
     */
    public function getRoleList()
    {
        /** @var \yii\rbac\DbManager $auth */
        $auth = \Yii::$app->authManager;

        return $auth->getRolesByUser($this->getId());
    }

    /**
     * @return string[]
     */
    public function getRoleNameList()
    {
        return array_map(function (\yii\rbac\Role $item) {
            return $item->name;
        }, $this->getRoleList());
    }
}
