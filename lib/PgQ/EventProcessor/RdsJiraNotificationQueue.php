<?php
/**
 * Консьюмер, который разгребает очередь создания версия в jira
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsJiraNotificationQueue  --queue-name=rds_jira_notification_queue --consumer-name=rds_jira_notification_queue_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_RdsJiraNotificationQueue extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        $id = $event->getData()['obj_id'];
        $this->debugLogger->message("Processing event #$id");
        /** @var $item JiraNotificationQueue */
        $item = JiraNotificationQueue::model()->findByPk($id);

        if (!$item) {
            $this->debugLogger->warning("Skip event #$id as not exists at DB");
            return;
        }

        $project = $item->project;

        //WT notification
        Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendReleaseReleased'}($project->project_name, $item->jnq_new_version);

        //SMS
        if ($item->jnq_old_version < $item->jnq_new_version) {
            $title = "Deployed $project->project_name v.$item->jnq_new_version";
        } else {
            $title = "Reverted $project->project_name v.$item->jnq_new_version";
        }
        foreach (explode(",", \Yii::app()->params['notify']['use']['phones']) as $phone) {
            if (!$phone) continue;
            Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
        }

        //finam notification
        if ($project->project_notification_email && $project->project_notification_subject) {
            $subject = sprintf($project->project_notification_subject, $item->jnq_new_version);
            if ($item->jnq_old_version > $item->jnq_new_version) {
                $subject = "ОТКАЧЕНО $subject";
            }

            $c = new CDbCriteria();
            $c->compare("jira_commit_build_tag", ">".min($project->project_name."-".$item->jnq_old_version, $project->project_name."-".$item->jnq_new_version));
            $c->compare("jira_commit_build_tag", "<=".max($project->project_name."-".$item->jnq_old_version, $project->project_name."-".$item->jnq_new_version));
            $jiraCommits = JiraCommit::model()->findAll($c);
            $tickets = [];
            /** @var $jiraCommit JiraCommit */
            foreach ($jiraCommits as $jiraCommit) {
                $tickets[] = $jiraCommit->jira_commit_ticket;
            }
            $tickets = array_unique($tickets);

            $ticketsInfo = [];
            $jira = new JiraApi($this->debugLogger);
            foreach ($tickets as $ticket) {
                try {
                    $ticketsInfo[$ticket] = $jira->getTicketInfo($ticket);
                } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
                    if ($e->getHttpCode() != 404) {
                        throw $e;
                    }
                }
            }

            $text = "Затронутые задачи: <br /><table>\r\n";
            foreach ($ticketsInfo as $ticket => $info) {
                $text .= "<tr><td><a href='http://jira/browse/$ticket'><b>$ticket</b></a></td><td> &ndash; {$info['fields']['summary']}</td></tr>\r\n";
            }
            $text .= "</table>\r\n";
            mail($project->project_notification_email, $subject, $text, "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n");
        }
    }
}