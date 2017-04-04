<?php
namespace app\modules\SingleLogin\components;

class SingleAuthManager extends \yii\rbac\PhpManager
{
    /**
     * {@inheritdoc}
     */
    public function load()
    {
        parent::load();

        foreach ((array) \Yii::$app->session['userRights'] as $role) {
            $this->assign($role, \Yii::$app->user->id);
        }
    }
}
