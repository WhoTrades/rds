<?php
namespace app\modules\SingleLogin\controllers;

use app\controllers\Controller;

class AuthController extends Controller
{
    /**
     * @param string $code
     */
    public function actionLogin($code)
    {
        $auth = \Yii::$app->getModule('SingleLogin')->auth;
        $user = $auth->authorize($code);

        if ($user) {
            \Yii::$app->user->login($user, 3600*24*30);
        }

        $this->redirect('/');
    }

    /**
     * @return void
     */
    public function actionRights()
    {
        $result = array(
            'result' => array(
                'data' => array(
                    'status' => 'OK',
                    'list' => array_keys(\Yii::$app->authManager->getAuthItems()),
                ),
            ),
        );
        echo json_encode($result);
    }
}
