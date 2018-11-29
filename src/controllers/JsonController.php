<?php
namespace whotrades\rds\controllers;

use whotrades\rds\models\Project;
use yii\web\HttpException;
use whotrades\rds\models\ReleaseRequest;

class JsonController extends Controller
{
    // ag: Disable debugModule for API controllers
    protected $disableDebugModule = true;

    public $enableCsrfValidation = false;

    /**
     * Возвращает список пакетов, установленных на PROD сервера
     * @param string $project название проекта (Поле в БД project.project_name)
     * @param int    $limit   максимальное количество результатов. Работает только если $project указан
     * @param string $format  формат ответа. null (json) или не null (plain text)
     * @throws HttpException
     */
    public function actionGetInstalledPackages($project = null, $limit = null, $format = null)
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

        if ($format === null) {
            return $this->asJson($result);
        }

        return implode(" ", $result);
    }

    /**
     * Метод используется git хуком update.php, который проверяет можно ли команде CRM пушить в хотрелизную ветку после кодфриза и до релиза
     * @param string $projectName
     */
    public function actionGetProjectCurrentVersion($projectName)
    {
        /** @var $project Project */
        $project = Project::findByAttributes(['project_name' => $projectName]);

        $this->asJson($project ? $project->project_current_version : null);
    }
}
