<?php
/**
 * @example php yii.php remove-packages/index
 */
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

        // ag: Find old releases
        $releaseRequestsQuery = ReleaseRequest::find()->joinWith(['project']);
        $releaseRequestsQuery->where('rr_build_version <> project_current_version');
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

        /** @var $releaseRequest ReleaseRequest */
        foreach ($releaseRequests as $releaseRequest) {
            $project = $releaseRequest->project;

            $minTimeAtProdLocal = Yii::$app->params['garbageCollector'][$project->project_name]['minBuildsCountBeforeActive'] ?? $minTimeAtProd;
            $thresholdDateTime = new \DateTime("-{$minTimeAtProdLocal}");
            $releaseRequestDateTime = new \DateTime($releaseRequest->obj_created);
            $diffDateInterval = $thresholdDateTime->diff($releaseRequestDateTime);
            if ($diffDateInterval->invert === 0) {
                Yii::info("Skip destroying release request. Waiting for {$diffDateInterval->format("%d days %h hours")} (release_request={$releaseRequest->getBuildTag()})");
                continue;
            }

            if ($releaseRequest->rr_build_version == $project->project_current_version) {
                // an: Ну никак нельзя удалять ту версию, что сейчас зарелижена
                Yii::info("Skip destroying release request. It is active. (release_request={$releaseRequest->getBuildTag()})");
                continue;
            }

            $count = $this->countInstalledBuildsBetweenVersions($project->obj_id, $releaseRequest->rr_build_version, $project->project_current_version);

            // ag: Destroy old or manually deleted releases
            $minBuildsCountBeforeActiveProject = Yii::$app->params['garbageCollector'][$project->project_name]['minBuildsCountBeforeActive'] ?? $minBuildsCountBeforeActive;
            if ($count > $minBuildsCountBeforeActiveProject || $releaseRequest->obj_status_did == Status::DELETED) {
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
        return ReleaseRequest::find()
            ->andWhere(['rr_project_obj_id' => $projectId])
            ->andWhere("string_to_array(rr_build_version, '.')::int[] >= string_to_array('" . addslashes($startVersion) . "', '.')::int[]")
            ->andWhere("string_to_array(rr_build_version, '.')::int[] <= string_to_array('" . addslashes($endVersion) . "', '.')::int[]")
            ->andWhere(['in', 'rr_status', ReleaseRequest::getInstalledStatuses()])
            ->count();
    }
}
