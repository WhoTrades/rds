<?php

class AuthController extends Controller
{
	public function actionLogin($code)
	{
        $auth = Yii::app()->getModule('SingleLogin')->auth;
        $user = $auth->authorize($code);

        if ($user) {
            Yii::app()->user->login($user, 3600*24*30);
        }


		$this->redirect('/');
	}

    public function actionRights()
    {
        $result = array(
            'result' => array(
                'data' => array(
                    'status' => 'OK',
                    'list' => array('admin', 'developer'),
                ),
            )
        );
        echo json_encode($result);
    }
}