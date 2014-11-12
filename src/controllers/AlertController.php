<?php

class AlertController extends Controller
{
    const TIMEZONE = "Europe/Moscow";

    public $pageTitle = 'Сигнализация';

    const ALERT_TIMEOUT = '5 minute';
    const ALERT_WAIT_TIMEOUT = '10 minutes';
    const ALERT_START_HOUR = 15;
    const ALERT_END_HOUR = 20;

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

    public static function canBeLampLightedByTimeRanges()
    {
        //an: Лампа всегда выключена до 10 утра МСК и после 20:00 МСК
        $prev = date_default_timezone_get();
        date_default_timezone_set(self::TIMEZONE);
        $hourAtMoscow = (int)date("H");
        date_default_timezone_set($prev);

        return $hourAtMoscow > self::ALERT_START_HOUR && $hourAtMoscow < self::ALERT_END_HOUR;
    }

    private function getLampStatus($lampName)
    {
        if (!self::canBeLampLightedByTimeRanges()) {
            return false;
        }
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
