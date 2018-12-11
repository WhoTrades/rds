<?php

use \whotrades\rds\migrations\base;
use \whotrades\rds\models\User\User;
use yii\rbac\DbManager;

/**
 * Class m181211_175506_rbac_init
 */
class m181211_175506_rbac_init extends base
{
    /**
     */
    public function up()
    {
        $auth = $this->getAuthManager();

        $adminRole = $auth->createRole('admin');
        $auth->add($adminRole);

        $developerRole = $auth->createRole('developer');
        $auth->add($developerRole);

        $auth->addChild($adminRole, $developerRole);

        $userList = User::find()->all();

        /** @var User $user */
        foreach ($userList as $user) {
            $auth->assign($developerRole, $user->getId());
        }
    }

    public function down()
    {
        $this->getAuthManager()->removeAll();
    }

    /**
     * @return DbManager
     *
     * @throws yii\base\InvalidConfigException
     */
    protected function getAuthManager()
    {
        $authManager = Yii::$app->getAuthManager();
        if (!$authManager instanceof DbManager) {
            throw new \yii\base\InvalidConfigException('You should configure "authManager" component to use database before executing this migration.');
        }

        return $authManager;
    }
}
