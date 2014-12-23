<?php
class JiraController extends Controller
{
    public function actionGotoJiraTicketsByReleaseRequest($id)
    {
        /** @var $rr ReleaseRequest */
        $rr = ReleaseRequest::model()->findByPk($id);
        if (!$rr) {
            throw new CHttpException(404, "Release Request #$id not found");
        }

        if ($rr->isUsedStatus()) {
            $rr2 = $rr->getOldReleaseRequest();
        } else {
            $rr2 = $rr->getUsedReleaseRequest();
        }
        if (!$rr2) {
            throw new CHttpException(404, "Нет версии, с которой сравнивать тикеты");
        }

        list($from, $to) = [min($rr->getBuildTag(), $rr2->getBuildTag()), max($rr->getBuildTag(), $rr2->getBuildTag())];

        $c = new CDbCriteria();
        $c->compare('jira_commit_build_tag', ">".$from);
        $c->compare('jira_commit_build_tag', "<=".$to);

        $list = JiraCommit::model()->findAll($c);
        $tags = array_unique(array_map(function(JiraCommit $val){ return $val->jira_commit_build_tag;}, $list));

        $url = "http://jira/issues/?jql=".urlencode('fixVersion IN ('.implode(',', $tags).')');

        $this->redirect($url);
    }

    public function actionIndex()
    {
        $this->render('index', ['projects' => Yii::app()->params['jiraProjects']]);
    }

    public function actionTriggerView()
    {
        echo "<pre><b>LAST 1Kb</b>\n";
        $text = file_get_contents("/tmp/jira.txt");

        echo substr($text, -1000);
    }

    public function actionTrigger($key)
    {
        echo $key;
        file_put_contents("/tmp/jira.txt", var_export($_REQUEST, 1)."\n", FILE_APPEND);
        file_put_contents("/tmp/jira.txt", file_get_contents("php://stdin"), FILE_APPEND);
        file_put_contents("/tmp/jira.txt", "==========\n\n", FILE_APPEND);
    }

    public function actionTicketHide($project)
    {
        ob_get_clean();
        $jiraApi = new JiraApi(Yii::app()->debugLogger);

        $jql = "project=$project and fixVersion is not empty and status != Closed";
        $blockers = $jiraApi->getTicketsByJql($jql);
        $blockedFixVersions = [];
        foreach ($blockers['issues'] as $ticket) {
            foreach ($ticket['fields']['fixVersions'] as $fixVersion) {
                $blockedFixVersions[$fixVersion['id']]['name'] = $fixVersion['name'];
                $blockedFixVersions[$fixVersion['id']]['blockers'][] = $ticket;;
            }
        }

        if ($blockedFixVersions) {
            $jql = "project=$project and fixVersion IN (".implode(", ", array_keys($blockedFixVersions)).") and status = Closed";
            $blocked = $jiraApi->getTicketsByJql($jql);
        } else {
            $blocked = [];
        }

        $map = [];
        foreach ($blocked['issues'] as $ticketBlocked) {
            foreach ($blockers['issues'] as $ticketBlockers) {
                $blocks = false;
                foreach ($ticketBlocked['fields']['fixVersions'] as $fixVersion1) {
                    foreach ($ticketBlockers['fields']['fixVersions'] as $fixVersion2) {
                        if ($fixVersion1['id'] == $fixVersion2['id']) {
                            $blocks = true;
                            break 2;
                        }
                    }
                }

                $map[$ticketBlocked['key']][$ticketBlockers['key']] = $blocks;
                $map[$ticketBlockers['key']][$ticketBlocked['key']] = $blocks;
            }
        }

        usort($blocked['issues'], function($a, $b) use ($map){
            return count(array_filter($map[$a['key']])) - count(array_filter($map[$b['key']]));
        });

        usort($blockers['issues'], function($a, $b) use ($map){
            return count(array_filter($map[$b['key']])) - count(array_filter($map[$a['key']]));
        });

        $blockers['issues'] = array_filter($blockers['issues'], function($a) use ($map){
            return count(array_filter($map[$a['key']])) > 0;
        });

        $this->render('ticketHide', [
            'project'   => $project,
            'blockers'  => $blockers,
            'blocked'   => $blocked,
        ]);
    }

    public function actionVersions($project, $released = null)
    {
        ob_get_clean();
        $jiraApi = new JiraApi(Yii::app()->debugLogger);
        $versions = $jiraApi->getAllVersions($project);

        $this->render('versions', [
            'versions' => $versions,
            'released' => $released,
        ]);
    }
}
