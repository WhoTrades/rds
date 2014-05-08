<?php
class SingleAuthManager extends CPhpAuthManager
{
    public function load()
    {
        parent::load();

        //Временно всем выдаем админа
        $this->assign('admin', \Yii::app()->user->id);
    }
}
