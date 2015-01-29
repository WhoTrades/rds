<?php
class GitController extends Controller
{
    public $pageTitle = 'Git';

    public function actionIndex()
    {
        $sql = "select distinct jf_branch, jf_blocker_commits from rds.jira_feature
        where jf_status='removing' and not jf_blocker_commits is null
        group by jf_branch, jf_blocker_commits";
        /** @var $pdo PDO */
        $pdo = Yii::app()->db->getPdoInstance();
        $list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($list as $key => $val) {
            $list[$key]['jf_blocker_commits'] = json_decode($list[$key]['jf_blocker_commits']);
        }

        $this->render('index', [
            'list' => $list,
        ]);
    }
}
