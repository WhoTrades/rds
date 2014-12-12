<?php
/**
 * Консьюмер запрашивает сборку в teamcity всех наших репозиториев в фичевой ветке и складывает в таблицу
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsTeamCityRunTest  --queue-name=rds_teamcity_run_test --consumer-name=rds_teamcity_run_test_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_RdsTeamCityRunTest extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        $teamCity = new TeamcityClient\WtTeamCityClient();

        $teamCityRunTest = TeamcityRunTest::model()->findByPk($event->getData()['obj_id']);

        $this->debugLogger->message("Processing test run with attributes: ".json_encode($teamCityRunTest->attributes));

        /** @var $transaction CDbTransaction*/
        $transaction = \TeamcityBuild::model()->getDbConnection()->beginTransaction();
        foreach ($teamCity->getBuildTypesList() as $buildType) {
            /** @var $buildType SimpleXMLElement*/
            if (in_array($buildType['name'], Yii::app()->params['teamCityEnabledBuildTypes'])) {
                $data = $buildType->attributes();

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
        }
        //an: Оформляем все в транзакцию, что бы не случилось race condition (проверялка тестов будет смотреть есть ли
        //ещё незавершенные тесты, и если мы вдруг будем тесты вставлять медленнее проверялки - она решит что все тесты
        //закончились ещё до того как мы все вставим. А если мы тесты начнем, а до комита транзакции не дойдем - не страшно,
        //просто немного нагрузит тимсити :) если такое будет часто случаться - нужно будет что-то думать
        $transaction->commit();
    }
}