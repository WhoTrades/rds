<?php
namespace whotrades\rds\commands;

use whotrades\rds\components\Status;
use whotrades\rds\models\Project2worker;
use whotrades\rds\models\ReleaseRequest;
use whotrades\RdsSystem\Cron\SingleInstanceController;
use whotrades\RdsSystem\Message\DropReleaseRequest;
use Yii;

class RemovePackagesController extends SingleInstanceController
{
    public $limit = 1000;
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
        $model = (new \whotrades\RdsSystem\Factory())->getMessagingRdsMsModel();

        $maxDate = date('Y-m-d H:i:s', strtotime("-" . $minTimeAtProd));

        // ag: Find old releases
        $releaseRequestsQuery = ReleaseRequest::find()->joinWith(['project'])->where(['<=', 'release_request.obj_created', $maxDate]);
        $releaseRequestsQuery->andWhere('rr_build_version <> project_current_version');
        $releaseRequestsQuery->andWhere('release_request.obj_status_did <> ' . Status::DESTROYED);
        if ($this->projectName) {
            $releaseRequestsQuery->andWhere(['project_name' => $this->projectName]);
        }
        $releaseRequestsQuery->limit($this->limit);
        $releaseRequestsQuery->orderBy('obj_created asc');
        $releaseRequests = $releaseRequestsQuery->all();

        // ag: Find manually deleted releases
        $releaseRequestsQuery = ReleaseRequest::find()->joinWith(['project'])->where('release_request.obj_status_did = ' . Status::DELETED);
        $releaseRequestsQuery->andWhere('rr_build_version <> project_current_version');
        $releaseRequests = array_merge($releaseRequests, $releaseRequestsQuery->all());

        foreach ($releaseRequests as $releaseRequest) {
            /** @var $releaseRequest ReleaseRequest */
            $project = $releaseRequest->project;

            if ($releaseRequest->rr_build_version == $project->project_current_version) {
                // an: Ну никак нельзя удалять ту версию, что сейчас зарелижена
                Yii::info("Active project and version (build={$releaseRequest->getBuildTag()})");
                continue;
            }

            $count = $this->countInstalledBuildsBetweenVersions($project->obj_id, $releaseRequest->rr_build_version, $project->project_current_version);

            // ag: Destroy old or manually deleted releases
            if ($count > $minBuildsCountBeforeActive || $releaseRequest->obj_status_did == Status::DELETED) {
                Yii::info("Sending delete message for release_request={$releaseRequest->getBuildTag()}, created=$releaseRequest->obj_created");
                if ($this->dryRun) {
                    Yii::info("DRY RUN - skip sending packet");
                    continue;
                }
                foreach ($releaseRequest->builds as $build) {
                    $model->sendDropReleaseRequest(
                        $build->worker->worker_name,
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
            ->andWhere("string_to_array(rr_build_version, '.')::int[] >= string_to_array('" . addslashes($startVersion) . "', '.')::int[]")
            ->andWhere("string_to_array(rr_build_version, '.')::int[] <= string_to_array('" . addslashes($endVersion) . "', '.')::int[]")
            ->andWhere(['in', 'rr_status', ReleaseRequest::getInstalledStatuses()])
            ->andWhere('release_request.obj_status_did <> ' . Status::DESTROYED) // ag: exclude destroyed manually releases in the middle
            ->count();

        return $count;
    }
}
