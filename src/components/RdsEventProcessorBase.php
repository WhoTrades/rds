<?php
namespace app\components;

abstract class RdsEventProcessorBase extends \PgQ\EventProcessor\EventProcessorBase
{
    /** @return \GraphiteSystem\Graphite */
    public function getEventProcessorGraphite()
    {
        return \Yii::$app->graphite->getGraphite();
    }

    /**
     * @param string $queueName
     * @param string $consumerName
     * @param string $strategy
     * @param string $dnsName
     * @param int $partitionsCount
     * @param array $parameters
     * @param int $verbosity
     * @param string $entryScript
     * @return \Cronjob\ConfigGenerator\PgqConsumer
     */
    public static function getPgqConsumer(
        $queueName,
        $consumerName,
        $strategy = null,
        $dnsName = null,
        $partitionsCount = null,
        $parameters = array(),
        $verbosity = 3,
        $entryScript = 'db/pgq/process'
    ) {
        return new \Cronjob\ConfigGenerator\PgqConsumer(
            get_called_class(),
            $queueName,
            $consumerName,
            $strategy,
            $dnsName,
            $partitionsCount,
            $parameters,
            $verbosity,
            $entryScript
        );
    }
}
