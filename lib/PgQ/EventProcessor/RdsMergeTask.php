<?php
/**
 * Консьюмер запрашивает сборку в teamcity всех наших репозиториев в фичевой ветке и складывает в таблицу
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsMergeTask  --queue-name=rds_merge_task --consumer-name=rds_merge_task_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_RdsMergeTask extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        $mergeTask = MergeTask::model()->findByPk($event->getData()['obj_id']);

        $this->debugLogger->message("Processing running merge task: ".json_encode($mergeTask->attributes));

        $dir = Yii::app()->params['merge']['poolDir'];
    }
}