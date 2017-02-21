<?php
namespace app\modules\SingleLogin\components;

class SingleAuthManager extends \yii\rbac\PhpManager
{
    public function load()
    {
        parent::load();

        foreach (\Yii::$app->session['userRights'] as $role) {
            $this->assign($role, \Yii::$app->user->id);
        }
    }
}
