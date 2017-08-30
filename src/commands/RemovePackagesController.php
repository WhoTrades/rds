<?php
namespace app\commands;

use app\models\Project2worker;
use app\models\ReleaseRequest;
use RdsSystem\Cron\SingleInstanceController;
use RdsSystem\Message\DropReleaseRequest;
use Yii;

class RemovePackagesController extends SingleInstanceController
{
    public function actionIndex()
    {
        $minTimeAtProd = Yii::$app->params['garbageCollector']['minTimeAtProd'];
        $minBuildsCountBeforeActive = Yii::$app->params['garbageCollector']['minBuildsCountBeforeActive'];
        $model = (new \RdsSystem\Factory())->getMessagingRdsMsModel();

        $maxDate = date('Y-m-d H:i:s', strtotime($minTimeAtProd));
        $releaseRequests = ReleaseRequest::find()->joinWith(['project'])->where(['<=', 'release_request.obj_created', $maxDate])->all();

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
                Yii::info("Sending delete message for release_request=$releaseRequest->rr_build_version");
                foreach ($project->project2workers as $p2w) {
                    /** @var $p2w Project2worker */
                    $worker = $p2w->worker;
                    $model->sendDropReleaseRequest(
                        $worker->worker_name,
                        new DropReleaseRequest(
                            $project->project_name,
                            $releaseRequest->rr_build_version,
                            $project->script_migration_remove,
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
