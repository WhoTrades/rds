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
        foreach ($versions as $version) {
            $this->debugLogger->message("Processing release-$version->rv_version");
            /** @var $version ReleaseVersion */
            $url = "http://ci.whotrades.net:8111/httpAuth/app/rest/builds/?count=10000&locator=branch:release-$version->rv_version";

            $analyzedBuildTypeIds = [];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_USERPWD, "rest:rest123");
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $text = curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                $this->debugLogger->error("Can't fetch url $url: $error");
                continue;
            }

            $xml = simplexml_load_string($text);
            $errors = [];
            foreach ($xml->build as $build) {
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

            if (empty($alertLog) || $alertLog->alert_text != $text || $alertLog->alert_status != $status) {
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

                if ($status != AlertLog::STATUS_OK) {
                    $this->debugLogger->message("Sending alert email");
                    mail(\Config::getInstance()->serviceRds['alerts']['lampOnEmail'], "Ошибки в тестах, через 5 минут загорится лампа", "Ошибки: $text", "From: RDS alerts\r\n");
                } else {
                    $this->debugLogger->message("Sending ok email");
                    mail(\Config::getInstance()->serviceRds['alerts']['lampOffEmail'], "Ошибки в тестах исправлены, лампа выключена", "Ошибки были тут: ".$alertLog->alert_text, "From: RDS alerts\r\n");
                }
            }
        }
    }
}
