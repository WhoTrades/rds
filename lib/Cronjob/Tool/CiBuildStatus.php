<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=CiBuildStatus -vv
 */
class Cronjob_Tool_CiBuildStatus extends RdsSystem\Cron\RabbitDaemon
{
    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $versions = ReleaseVersion::model()->findAll();
        $client = new \TeamcityClient\WtTeamCityClient();

        foreach ($versions as $version) {
            $this->debugLogger->message("Processing release-$version->rv_version");
            /** @var $version ReleaseVersion */

            $analyzedBuildTypeIds = [];
            $builds = $client->getBuildsByBranch("release-$version->rv_version");
            $errors = [];
            foreach ($builds as $build) {
                //an: Выше эта сборка уже была проанализирована, игнорируем
                if (in_array($build['buildTypeId'], $analyzedBuildTypeIds)) {
                    continue;
                }
                if ($build['status'] == 'UNKNOWN') {
                    continue;
                }
                $analyzedBuildTypeIds[] = (string)$build['buildTypeId'];
                if ($build['status'] == 'FAILURE') {
                    $ch = curl_init($build['webUrl']);
                    curl_setopt($ch, CURLOPT_USERPWD, "rest:rest123");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $text = curl_exec($ch);
                    if (preg_match('~<title>[^<]*(PHPUnit_composer|acceptance-testing-tst)[^<]*</title>~', $text)) {
                        $errors[] = $build['webUrl'];
                    }
                }
            }

            $text = implode(", ", $errors);
            $status = $text ? AlertLog::STATUS_ERROR : AlertLog::STATUS_OK;
            $c = new CDbCriteria();
            $c->compare('alert_name', AlertLog::WTS_LAMP_NAME);
            $c->compare('alert_version', $version->rv_version);
            $c->order = 'obj_id desc';
            /** @var $alertLog AlertLog */
            $alertLog = AlertLog::model()->find($c);

            //an: Вырезаем номер билда, так как он будет постоянно меняться
            if (empty($alertLog) || preg_replace('~\?buildId=\d+~', '', $alertLog->alert_text) != preg_replace('~\?buildId=\d+~', '', $text) || $alertLog->alert_status != $status) {
                $this->debugLogger->message("Adding new record, status=$status, text=$text");
                $new = new AlertLog();
                $new->attributes = [
                    'alert_name' => AlertLog::WTS_LAMP_NAME,
                    'alert_text' => $text,
                    'alert_status' => $status,
                    'alert_version' => $version->rv_version,
                ];
                if (!$new->save()) {
                    $this->debugLogger->error("Can't save alertLog: ".json_encode($new->errors));
                }

                $receiver = $status == AlertLog::STATUS_OK
                    ? \Config::getInstance()->serviceRds['alerts']['lampOffEmail']
                    : \Config::getInstance()->serviceRds['alerts']['lampOnEmail'];

                $mailHeaders = "From: $receiver\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8";

                if ($status != AlertLog::STATUS_OK) {
                    $subject = "Ошибка в тестах номер № $new->obj_id, лампочка $new->alert_name";
                    $prev = date_default_timezone_get();
                    date_default_timezone_set(AlertController::TIMEZONE);
                    $text = "Ошибки: $text<br />\n";
                    if (AlertController::canBeLampLightedByTimeRanges()) {
                        $text .= "Лампа загорится через 5 минут в ".date("Y.m.d H:i:s", strtotime(AlertController::ALERT_TIMEOUT))." МСК<br />\n";
                    } else {
                        $text .= "Лампа загорится в ".AlertController::ALERT_START_HOUR.":00 МСК<br />\n";
                    }
                    date_default_timezone_set($prev);
                    if (preg_replace('~\?buildId=\d+~', '', $text) != preg_replace('~\?buildId=\d+~', '', $alertLog->alert_text)) {
                        $this->debugLogger->message("Sending alert email");
                        mail($receiver, $subject, $text, $mailHeaders);
                    }
                } else {
                    $subject = "Ошибка в тестах номер № $alertLog->obj_id, лампочка $alertLog->alert_name";
                    $this->debugLogger->message("Sending ok email");
                    $secondsTotal = time() - strtotime($alertLog->obj_created." ".AlertController::ALERT_TIMEOUT);
                    echo $secondsTotal."\n";
                    $text = "Лампа выключена, ошибок больше нет<br />\n";
                    if ($secondsTotal > 0) {
                        date_default_timezone_set("GMT");
                        $text .= "Лампа горела на протяжении ".date("H:i:s", $secondsTotal)." часов <br />\n";
                        date_default_timezone_set($prev);
                    } else {
                        $text .= "Лампа была потушена ещё до зажигания!<br />\n";
                    }
                    mail($receiver, $subject, $text, $mailHeaders);
                }
            }
        }
    }
}
