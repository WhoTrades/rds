<?php
class JsonController extends Controller
{
    public function actionGetReleaseRequests($project)
    {
        /** @var $Project Project */
        $Project = Project::model()->findByAttributes(['project_name' => $project]);
        if (!$Project) {
            throw new CHttpException(404, 'Project not found');
        }

        $releaseRequests = $Project->releaseRequests;

        $result = [];
        foreach ($releaseRequests as $releaseRequest) {
            /** @var $project Project */
            $result[] = array(
                'project' => $project,
                'version' => $releaseRequest->rr_build_version,
                'old_version' => $releaseRequest->rr_old_version,
            );
        }

        echo json_encode($result);
    }

    public function actionGetAllowedReleaseBranches()
    {
        $versions = ReleaseVersion::model()->findAll();

        $result = [];
        foreach ($versions as $version) {
            $result[] = 'release-'.$version->rv_version;
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }
}

