<?php
namespace app\components;

class WebUser extends CWebUser
{
    /**
     * @param string $id
     * @param string $name
     * @param string $states
     */
    protected function changeIdentity($id, $name, $states)
    {
        // an: Указываем false, что бы корректно работало с nutracker
        \Yii::$app->getSession()->regenerateID(false);
        $this->setId($id);
        $this->setName($name);
        $this->loadIdentityStates($states);
    }
}
