<?php

namespace whotrades\rds\controllers;

use whotrades\rds\models\forms\StopDeploymentForm;
use whotrades\rds\models\Log;
use whotrades\rds\models\RdsDbConfig;

class SystemController extends ControllerRestrictedBase
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        $config = RdsDbConfig::get();
        if (!$config) {
            $config = new RdsDbConfig([]);
            $config->save();
        }

        $model = new StopDeploymentForm();

        if ($model->load(\Yii::$app->request->post())) {
            if ($model->validate()) {
                $config->deployment_enabled = $model->status;
                $config->deployment_enabled_reason = $model->reason;
                $config->save();

                if ($config->deployment_enabled) {
                    $title  = \Yii::t('rds', 'notif_deployment_enabled');
                    $body   = \Yii::t('rds', 'notif_deployment_enabled_body');
                    $reason = '';
                    $type   = 'success';
                } else {
                    $title  = \Yii::t('rds', 'notif_deployment_disabled');
                    $body   = \Yii::t('rds', 'notif_deployment_disabled_body');
                    $reason = \Yii::t('rds', 'notif_deployment_disabled_reason', [$model->reason ?: 'неизвестно']);
                    $type   = 'danger';
                }

                Log::createLogMessage($title . $reason);

                \Yii::$app->webSockets->send(
                    'popup_message',
                    [
                        'title' => $title,
                        'body'  => $body . ' ' . $reason,
                        'type'  => $type,
                    ]
                );

                $this->refresh();
            }
        }

        return $this->render('index', [
            'config' => $config,
            'model' => $model,
        ]);
    }
}
