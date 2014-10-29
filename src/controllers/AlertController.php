<?php

class AlertController extends Controller
{
    public $pageTitle = 'Сигнализация';

    const ALERT_TIMEOUT = '5 minutes';
    const ALERT_WAIT_TIMEOUT = '10 minutes';

    public function actionIndex()
    {
        if (!empty($_POST['disable'])) {
            $conf = \RdsDbConfig::get();
            foreach ($_POST['disable'] as $key => $tmp) {
                $conf->{$key."_timeout"} = date("Y-m-d H:i:s", strtotime(self::ALERT_WAIT_TIMEOUT));
            }
            $conf->save();
            $this->redirect("/alert/");
        }

        $this->render('index', [
            'lamps' => [
                AlertLog::WTS_LAMP_NAME => [
                    'status' => $this->getLampStatus(AlertLog::WTS_LAMP_NAME),
                ],
            ],
        ]);
    }


    public function actionGetLampStatusJson($lampName)
    {
        $result = ['alert' => $this->getLampStatus($lampName)];

        echo json_encode($result, JSON_PRETTY_PRINT);
    }


    private function getLampStatus($lampName)
    {
        $result = false;
        $versions = ReleaseVersion::model()->findAll();
        foreach ($versions as $version) {
            /** @var $version ReleaseVersion */
            $c = new CDbCriteria();
            $c->compare('alert_name', $lampName);
            $c->compare('alert_version', $version->rv_version);
            $c->order = 'obj_id desc';
            $last = AlertLog::model()->find($c);
            $var = "{$lampName}_timeout";

            if ($last && \RdsDbConfig::get()->$var < date('Y-m-d H:i:s')) {
                $result = $result || (strtotime($last->obj_created) < strtotime('now -'.self::ALERT_TIMEOUT) && $last->alert_status == AlertLog::STATUS_ERROR);
            }
        }

        return $result;
    }
}
