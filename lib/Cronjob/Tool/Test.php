<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;
use RdsSystem\lib\CommandExecutor;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $teamCity = new \TeamcityClient\WtTeamCityClient();

        /** @var $teamCityRunTest TeamcityRunTest*/
        $teamCityRunTest = TeamcityRunTest::model()->findByPk(34);

        $jiraFeature = $teamCityRunTest->jiraFeature;
        $affectedRepositories = json_decode($jiraFeature->jf_affected_repositories);
        //an: всегда проверяем comon, lib-crm-system, trade-system
        $affectedRepositories[] = "comon";
        $affectedRepositories[] = "lib-crm-system";
        $affectedRepositories[] = "trade-system";

        $this->debugLogger->message("Processing test run with attributes: ".json_encode($teamCityRunTest->attributes));

        /** @var $transaction CDbTransaction*/
        $transaction = \TeamcityBuild::model()->getDbConnection()->beginTransaction();
        foreach ($teamCity->getBuildTypesList() as $buildType) {
            /** @var $buildType SimpleXMLElement*/
            if (!in_array($buildType['name'], Yii::app()->params['teamCityEnabledBuildTypes'])) {
                continue;
            }

            $data = $buildType->attributes();

            $buildTypeFull = $teamCity->getBuildType($data['id']);

            $ok = false;

            foreach ($buildTypeFull->children()->{'vcs-root-entries'}->children()->children() as $entry) {
                if (in_array($entry['name'], $affectedRepositories)) {
                    $ok = true;
                    break;
                }
            }

            if (!$ok){
                continue;
            }

            $this->debugLogger->message("Starting build {$buildType['id']}");
            $result = $teamCity->startBuild($buildType['id'], $teamCityRunTest->trt_branch, "Plan test of feature");

            $this->debugLogger->debug("Teamcity response Result: ".json_encode($result));

            $teamCityBuild = new TeamcityBuild();
            $teamCityBuild->attributes = [
                'tb_run_test_obj_id' => $teamCityRunTest->obj_id,
                'tb_build_type_id' => $buildType['id'],
                'tb_branch' => $teamCityRunTest->trt_branch,
                'tb_status' => TeamCityBuild::STATUS_QUEUED,
                'tb_url' => $result['webUrl'],
            ];

            $teamCityBuild->save(false);
        }
        //an: Оформляем все в транзакцию, что бы не случилось race condition (проверялка тестов будет смотреть есть ли
        //ещё незавершенные тесты, и если мы вдруг будем тесты вставлять медленнее проверялки - она решит что все тесты
        //закончились ещё до того как мы все вставим. А если мы тесты начнем, а до комита транзакции не дойдем - не страшно,
        //просто немного нагрузит тимсити :) если такое будет часто случаться - нужно будет что-то думать
        $transaction->commit();
    }
}

