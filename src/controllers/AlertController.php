<?php

class AlertController extends Controller
{
    const TIMEZONE = "Europe/Moscow";

    public $pageTitle = 'Сигнализация';

    const ALERT_TIMEOUT = '5 minute';
    const ALERT_WAIT_TIMEOUT = '10 minutes';
    const ALERT_START_HOUR = 10;
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

        if (!empty($_POST['ignore'])) {
            foreach($_POST['ignore'] as $id => $time) {
                $alertLog = AlertLog::model()->findByPk($id);

                if($alertLog) {
                    $alertLog->alert_ignore_timeout = date(DATE_ISO8601, strtotime($time));
                    $alertLog->save();
                }
            }
            $this->redirect("/alert/");
        }

        $lamps = [];

        foreach ([AlertLog::WTS_LAMP_NAME] as $lampName) {
            $lamps[$lampName] = [
                'status' => $this->getLampStatus($lampName),
                // 'timeout' => $this->getLampTimeout($lampName),
                'errors' => $this->getLampErrors($lampName),
                'ignores' => $this->getLampIgnores($lampName),
            ];
        }

        $this->render('index', [
            'lamps' => $lamps,
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

        return $hourAtMoscow >= self::ALERT_START_HOUR && $hourAtMoscow < self::ALERT_END_HOUR;
    }

    private function getLampStatus($lampName)
    {
        return $lampName != 'red_lamp_acceptance_tests';
        if (!self::canBeLampLightedByTimeRanges()) {
            return false;
        }
        $result = false;

        $lampTimeout = $this->getLampTimeout($lampName);

        if ($lampTimeout > date('Y-m-d H:i:s')) {
            return $result;
        }

        $errors = $this->getLampErrors($lampName);

        foreach ($errors as $error) {
            $result = $result || (strtotime($error->alert_detect_at) < strtotime('now -'.self::ALERT_TIMEOUT));
        }

        return $result;
    }

    /**
     * Список ошибок для данной лампой
     *
     * @param string $lampName название лампы
     *
     * @return AlertLog[]
     */
    private function getLampErrors($lampName)
    {
        $c = new CDbCriteria();
        $c->compare('alert_lamp', $lampName);
        $c->compare('alert_status', AlertLog::STATUS_ERROR);
        $c->compare('alert_ignore_timeout', '<'.date(DATE_ISO8601));

        $c->order = 'alert_detect_at DESC';

        $alertLog = AlertLog::model()->findAll($c);

        return $alertLog;
    }

    /**
     * Список событий, которые игнорируются данной лампой
     *
     * @param string $lampName название лампы
     *
     * @return AlertLog[]
     */
    private function getLampIgnores($lampName)
    {
        $c = new CDbCriteria();
        $c->compare('alert_lamp', $lampName);
        $c->compare('alert_ignore_timeout', '>'.date(DATE_ISO8601));

        $c->order = 'alert_ignore_timeout ASC';

        $alertLog = AlertLog::model()->findAll($c);

        return $alertLog;
    }

    /**
     * Время, до которого выключена лампа
     *
     * @param string $lampName
     *
     * @return string
     */
    private function getLampTimeout($lampName)
    {
        return \RdsDbConfig::get()->{$lampName . "_timeout"};
    }
}
