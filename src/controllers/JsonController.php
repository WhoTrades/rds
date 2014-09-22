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

    public function actionRemoveReleaseRequest($projectName, $version)
    {
        $project = Project::model()->findByAttributes(['project_name' => $projectName]);
        if (!$project) {
            throw new CHttpException(404, 'Build not found');
        }

        $rr = ReleaseRequest::model()->findByAttributes([
            'rr_project_obj_id' => $project->obj_id,
            'rr_build_version' => $version,
        ]);

        if ($rr) {
            $rr->delete();
        }

        echo json_encode(array('ok' => true));
    }
}

