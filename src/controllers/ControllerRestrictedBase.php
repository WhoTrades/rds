<?php
/**
 * Base controller with restricted access
 */

namespace whotrades\rds\controllers;

class ControllerRestrictedBase extends Controller
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['admin', 'developer']],
                ],
                'denyCallback' => function ($rule, $action) {
                    $this->redirect('/');
                },
            ],
        ];
    }
}
