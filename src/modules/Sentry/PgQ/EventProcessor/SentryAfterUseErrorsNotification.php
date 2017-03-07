<?php
/**
 * Консьюмер, который двигает статусы тикетов из Ready for deploy -> Ready for acceptance в случае выкатывания релиза, и обратно в случае откатывания
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=SentryAfterUseErrorsNotification \
   --queue-name=rds_jira_use --consumer-name=sentry_after_use_errors_notification_consumer --partition=1 --dsn-name=DSN_DB4 --strategy=simple+retry -vvv \
   process_queue
 */

class PgQ_EventProcessor_SentryAfterUseErrorsNotification extends app\components\RdsEventProcessorBase
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
            $this->debugLogger->message("Revert detected (from $tagFrom to $tagTo),skip it");

            return;
        }

        if (time() - strtotime($data['obj_created']) < self::INTERVAL_FROM_USE) {
            $interval = self::INTERVAL_FROM_USE - (time() - strtotime($data['obj_created']));
            $this->debugLogger->message("Data is not ready, retry for later ($interval seconds)");
            $event->retry($interval);

            return;
        }

        $this->debugLogger->message("Processing event id={$event->getId()}, data = " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $res = parse_url(\Config::getInstance()->sentry['projects']['rds']['dsn']);
        $url = "{$res['scheme']}://{$res['host']}/";
        if (!preg_match('~([\w-]+)-([\d.]+)$~', $tagTo, $ans)) {
            throw new \ApplicationException("Using invalid build version '$tagTo''");
        }
        $project = $ans[1];
        $buildVersion = $ans[2];


        $api = new \CompanyInfrastructure\SentryApi($this->debugLogger, $url);
        try {
            $errors = iterator_to_array($api->getNewFatalErrorsIterator('sentry', $project, $buildVersion));
        } catch (ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            if ($e->getHttpCode() == 404 && $e->getResponse() == '{"detail": ""}') {
                $this->debugLogger->warning("Project $project not integrated with sentry");

                return;
            } else {
                throw $e;
            }
        }

        if (empty($errors)) {
            $this->debugLogger->message("No errors, skip email");

            return;
        }

        $this->debugLogger->message("Errors count: " . count($errors));

        \Yii::$app->EmailNotifier->sentNewSentryErrors(
            $initiatorUserName,
            $tagTo,
            $errors
        );
    }
}
