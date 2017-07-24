<?php
/**
 * Консьюмер, который двигает статусы тикетов из Ready for deploy -> Ready for acceptance в случае выкатывания релиза, и обратно в случае откатывания
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=app\\modules\\Sentry\\PgQ\\EventProcessor\\SentryAfterUseErrorsNotification \
   --queue-name=rds_jira_use --consumer-name=sentry_after_use_errors_notification_consumer --partition=1 --dsn-name=DSN_DB4 --strategy=simple+retry -vvv \
   process_queue
 */
namespace app\modules\Sentry\PgQ\EventProcessor;

use Yii;
use PgQ;
use app\components\RdsEventProcessorBase;
use ApplicationException;
use ServiceBase\HttpRequest\Exception\ResponseCode;

class SentryAfterUseErrorsNotification extends RdsEventProcessorBase
{
    const SENTRY_WAIT_RETRY_TIMEOUT = 60;
    const INTERVAL_FROM_USE = 600;

    /**
     * @param \PgQ\Event $event
     * @throws ApplicationException
     */
    public function processEvent(PgQ\Event $event)
    {
        $data = $event->getData();

        $initiatorUserName = $data['jira_use_initiator_user_name'];
        $tagFrom = $data['jira_use_from_build_tag'];
        $tagTo = $data['jira_use_to_build_tag'];

        // an: На откаты не реагируем
        if ($tagFrom > $tagTo) {
            Yii::info("Revert detected (from $tagFrom to $tagTo),skip it");

            return;
        }

        if (time() - strtotime($data['obj_created']) < self::INTERVAL_FROM_USE) {
            $interval = self::INTERVAL_FROM_USE - (time() - strtotime($data['obj_created']));
            Yii::info("Data is not ready, retry for later ($interval seconds)");
            $event->retry($interval);

            return;
        }

        Yii::info("Processing event id={$event->getId()}, data = " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $res = parse_url(\Yii::$app->sentry->dsn);
        $url = "{$res['scheme']}://{$res['host']}/";
        if (!preg_match('~([\w-]+)-([\d.]+)$~', $tagTo, $ans)) {
            throw new \ApplicationException("Using invalid build version '$tagTo''");
        }
        $project = $ans[1];
        $buildVersion = $ans[2];

        $api = new \CompanyInfrastructure\SentryApi($url);
        try {
            $errors = iterator_to_array($api->getNewFatalErrorsIterator('sentry', $project, $buildVersion));
        } catch (ResponseCode $e) {
            if ($e->getHttpCode() == 404 && $e->getResponse() == '{"detail": ""}') {
                Yii::warning("Project $project not integrated with sentry");

                return;
            } else {
                throw $e;
            }
        }

        if (empty($errors)) {
            Yii::info("No errors, skip email");

            return;
        }

        Yii::info("Errors count: " . count($errors));

        \Yii::$app->EmailNotifier->sentNewSentryErrors(
            $initiatorUserName,
            $tagTo,
            $errors
        );
    }
}
