<?php
namespace app\commands;

use app\models\Project2worker;
use app\models\ReleaseRequest;
use RdsSystem\Cron\SingleInstanceController;
use RdsSystem\Message\DropReleaseRequest;
use Yii;

class RemovePackagesController extends SingleInstanceController
{
    public $limit = 30;
    public $dryRun = false;
    public $projectName = null;

    /**
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['dryRun', 'limit', 'projectName']);
    }

    /**
     * @return void
     */
    public function actionIndex()
    {
        $minTimeAtProd = Yii::$app->params['garbageCollector']['minTimeAtProd'];
        $minBuildsCountBeforeActive = Yii::$app->params['garbageCollector']['minBuildsCountBeforeActive'];
        $model = (new \RdsSystem\Factory())->getMessagingRdsMsModel();

        $maxDate = date('Y-m-d H:i:s', strtotime($minTimeAtProd));

        $releaseRequestsQuery = ReleaseRequest::find()->joinWith(['project'])->where(['<=', 'release_request.obj_created', $maxDate]);
        $releaseRequestsQuery->andWhere('rr_build_version <> project_current_version');
        $releaseRequestsQuery->andWhere('release_request.obj_status_did=1');
        if ($this->projectName) {
            $releaseRequestsQuery->andWhere(['project_name' => $this->projectName]);
        }
        $releaseRequestsQuery->limit($this->limit);
        $releaseRequestsQuery->orderBy('obj_created asc');
        $releaseRequests = $releaseRequestsQuery->all();

        foreach ($releaseRequests as $releaseRequest) {
            /** @var $releaseRequest ReleaseRequest */
            $project = $releaseRequest->project;

            if ($releaseRequest->rr_build_version == $project->project_current_version) {
                // an: Ну никак нельзя удалять ту версию, что сейчас зарелижена
                Yii::info("Active project and version (build={$releaseRequest->getBuildTag()})");
                continue;
            }

            $count = $this->countInstalledBuildsBetweenVersions($project->obj_id, $releaseRequest->rr_build_version, $project->project_current_version);

            if ($count > $minBuildsCountBeforeActive) {
                Yii::info("Sending delete message for release_request={$releaseRequest->getBuildTag()}, created=$releaseRequest->obj_created");
                if ($this->dryRun) {
                    Yii::info("DRY RUN - skip sending packet");
                    continue;
                }
                foreach ($project->project2workers as $p2w) {
                    /** @var $p2w Project2worker */
                    $worker = $p2w->worker;
                    $model->sendDropReleaseRequest(
                        $worker->worker_name,
                        new DropReleaseRequest(
                            $project->project_name,
                            $releaseRequest->rr_build_version,
                            str_replace("\r", "", $project->script_remove_release),
                            $project->getProjectServersArray()
                        )
                    );
                }
            }
        }
    }

    /**
     * @param int $projectId
     * @param string $startVersion
     * @param string $endVersion
     * @return int|string
     */
    private function countInstalledBuildsBetweenVersions(int $projectId, string $startVersion, string $endVersion)
    {
        $count = ReleaseRequest::find()
            ->andWhere(['rr_project_obj_id' => $projectId])
            ->andWhere("string_to_array(rr_build_version, '.')::int[] > string_to_array('" . addslashes($startVersion) . "', '.')::int[]")
            ->andWhere("string_to_array(rr_build_version, '.')::int[] < string_to_array('" . addslashes($endVersion) . "', '.')::int[]")
            ->andWhere(['in', 'rr_status', ReleaseRequest::getInstalledStatuses()])
            ->count();

        return $count;
    }
}
