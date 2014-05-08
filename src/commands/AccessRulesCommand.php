<?php
class AccessRulesCommand extends CConsoleCommand
{
    public function actionIndex()
    {
        $auth=Yii::app()->authManager;

        $auth->clearAll();

        $roleAdmin=$auth->createRole('admin');
        $roleDeveloper=$auth->createRole('developer');
        $roleProductOwner=$auth->createRole('productOwner');
        $roleReleaser=$auth->createRole('releaser');


        $auth->assign('admin', 'anaumenko@corp.finam.ru');

        $auth->save();
    }
}