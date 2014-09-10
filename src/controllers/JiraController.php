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
}
