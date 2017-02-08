<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=RdsAlertStatus -vv
 */
class Cronjob_Tool_RdsAlertStatus extends \Cronjob\Tool\ToolBase
{
    /**
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [];
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     *
     * @return int
     * @throws Exception
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        if (!Yii::$app->params['alertLampEnabled']) {
            $this->debugLogger->message("Lamp disabled");

            return 0;
        }

        foreach ($this->getDataProviderList() as $lampName => $dataProvider) {
            /** @var \AlertLog\AlertData[] $errors */
            $errors = [];
            foreach ($dataProvider->getData() as $alertData) {
                if ($alertData->isError()) {
                    $errors[$alertData->getName()] = $alertData;

                    $this->debugLogger->error("Error with {$alertData->getName()}, {$alertData->getText()}");
                }
            }

            $c = new CDbCriteria();
            $c->compare('alert_lamp', $lampName);
            $c->compare('alert_provider', $dataProvider->getName());
            $alertLog = AlertLog::findAll($c);

            foreach ($alertLog as $alert) {
                if (isset($errors[$alert->alert_name])) {
                    if ($alert->alert_status !== AlertLog::STATUS_ERROR) {
                        $alert->setStatus(AlertLog::STATUS_ERROR);
                        $this->sendErrorNotification($alert);
                    }
                    unset($errors[$alert->alert_name]);
                } else {
                    if ($alert->alert_status !== AlertLog::STATUS_OK) {
                        $alert->setStatus(AlertLog::STATUS_OK);
                        $this->sendOKNotification($alert);
                    }
                }
            }

            foreach ($errors as $error) {
                $new = new AlertLog();
                $new->attributes = [
                    'alert_lamp' => $lampName,
                    'alert_provider' => $dataProvider->getName(),
                    'alert_name' => $error->getName(),
                    'alert_text' => $error->getText(),
                    'alert_status' => $error->isError() ? AlertLog::STATUS_ERROR : AlertLog::STATUS_OK,
                ];

                $new->save();

                $this->sendErrorNotification($new);
            }
        }

        return 0;
    }

    /**
     * Отправка письма о появлении ошибки
     *
     * @param AlertLog $alertLog
     */
    private function sendErrorNotification(AlertLog $alertLog)
    {
        $subject = "Ошибка \"$alertLog->alert_name\", лампочка $alertLog->alert_lamp";
        $text = "$alertLog->alert_text<br />\n";

        $prev = date_default_timezone_get();
        date_default_timezone_set(AlertController::TIMEZONE);
        $text .= "Лампа включена<br />Взять ошибку в работу - http://rds.whotrades.net/alert/ (лампа погаснет на 10 минут)\n";
        date_default_timezone_set($prev);

        $receiver = \Config::getInstance()->serviceRds['alerts']['lampOnEmail'];
        $this->sendEmail($subject, $text, $receiver);

        $lamp = Lamp::model()->findByLampName($alertLog->alert_lamp);

        foreach ($lamp->getReceivers() as $phone) {
            $sms = "[ERROR] $alertLog->alert_name";
            Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $sms);
        }
    }

    /**
     * Отпарвка письма о пропадании ошибки
     *
     * @param AlertLog $alertLog
     */
    private function sendOKNotification(AlertLog $alertLog)
    {
        $subject = "Ошибка \"$alertLog->alert_name\", лампочка $alertLog->alert_lamp";
        $text = "Ошибка пропала<br />\n";
        $receiver = \Config::getInstance()->serviceRds['alerts']['lampOffEmail'];
        $this->sendEmail($subject, $text, $receiver);

        $lamp = Lamp::model()->findByLampName($alertLog->alert_lamp);

        foreach ($lamp->getReceivers() as $phone) {
            $sms = "[OK] $alertLog->alert_name";
            Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $sms);
        }
    }

    /**
     * Отправка письма
     *
     * @param string $subject тема письма
     * @param string $text текст письма
     * @param string $receiver адрес получателя
     */
    private function sendEmail($subject, $text, $receiver)
    {
        $from = \Config::getInstance()->serviceRds['alerts']['lampFromEmail'];
        $mailHeaders = "From: $from\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8";

        $this->debugLogger->message("Sending alert email");
        mail($receiver, $subject, $text, $mailHeaders);
    }


    /**
     * @return \AlertLog\IAlertDataProvider[]
     */
    private function getDataProviderList()
    {
        $config = \Config::getInstance()->serviceRds['alerts']['dataProvider'];

        return [
            AlertLog::WTS_LAMP_NAME => new \AlertLog\MonitoringDataProvider($this->debugLogger, 'Monitoring', $config['monitoring']['url']),
            AlertLog::CRM_LAMP_NAME => new \AlertLog\MonitoringDataProvider($this->debugLogger, 'Monitoring ', $config['monitoring']['url']),
            AlertLog::TEAM_CITY_LAMP_NAME => $this->getTeamCityDataProvider(['WhoTrades_AcceptanceTests_WtSmokeTestProd'], 'TeamCity: Smoke Tests'),
        ];
    }

    /**
     * Создает объединенный провайдер данных, который включает в себе провайдеры данных для каждого проекта
     *
     * @param array $projects Идентификаторы проектов
     * @param string $name Название провайдера данных
     *
     * @return \AlertLog\CompoundDataProvider
     */
    private function getTeamCityDataProvider(array $projects, $name = 'TeamCity')
    {
        $teamCityClient = new \CompanyInfrastructure\WtTeamCityClient($this->debugLogger);

        $dataProviders = [];

        foreach ($projects as $project) {
            $dataProviders[] = new \AlertLog\TeamCityDataProvider($this->debugLogger, $teamCityClient, $project);
        }

        return new \AlertLog\CompoundDataProvider($this->debugLogger, $name, $dataProviders);
    }
}
