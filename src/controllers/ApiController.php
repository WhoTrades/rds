<?php

namespace whotrades\rds\controllers;

class ApiController extends JsonController
{

    /**
     * @return void
     */
    public function actionHeartBeat(): void
    {
        $this->asJson([
            'status' => 'ok',
        ]);
    }
}
