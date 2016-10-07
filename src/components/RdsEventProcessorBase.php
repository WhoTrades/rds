<?php
abstract class RdsEventProcessorBase extends PgQ\EventProcessor\EventProcessorBase
{
    /** @return \GraphiteSystem\Graphite */
    public function getEventProcessorGraphite()
    {
        return \Yii::$app->graphite->getGraphite();
    }
}
