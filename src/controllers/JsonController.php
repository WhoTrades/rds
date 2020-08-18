<?php
namespace whotrades\rds\controllers;

use whotrades\rds\models\Project;
use yii\web\HttpException;
use yii\web\Response;
use whotrades\rds\models\ReleaseRequest;

class JsonController extends ControllerApiBase
{
    // ag: Disable debugModule for API controllers
    protected $disableDebugModule = true;

    public $enableCsrfValidation = false;

    /**
     * Возвращает список пакетов, установленных на PROD сервера
     * @param string $project название проекта (Поле в БД project.project_name)
     * @param int    $limit   максимальное количество результатов. Работает только если $project указан
     *
     * @return Response
     *
     * @throws HttpException
     */
    public function actionGetInstalledPackages($project = null, $limit = null)
    {
        $limit = $limit ?: 5;
        $result = [];

        $releaseRequests = ReleaseRequest::find()->andWhere(['in', 'rr_status', ReleaseRequest::getInstalledStatuses()])->orderBy('obj_id desc');

        if ($project) {
            $projectObj = Project::findOne([
                'project_name' => $project,
            ]);

            if (!$projectObj) {
                throw new HttpException(404, "Project $project not found");
            }
            $releaseRequests->andWhere(['rr_project_obj_id' => $projectObj->obj_id])->limit($limit);
        }
        $releaseRequests = $releaseRequests->all();

        foreach ($releaseRequests as $releaseRequest) {
            /** @var $releaseRequest ReleaseRequest */
            $result[] = $releaseRequest->getBuildTag();
        }

        return $this->asJson($result);
    }

    /**
     * Метод используется git хуком update.php, который проверяет можно ли команде CRM пушить в хотрелизную ветку после кодфриза и до релиза
     * @param string $projectName
     *
     * @return Response
     */
    public function actionGetProjectCurrentVersion($projectName)
    {
        /** @var $project Project */
        $project = Project::findByAttributes(['project_name' => $projectName]);

        return $this->asJson($project ? $project->project_current_version : null);
    }
}
