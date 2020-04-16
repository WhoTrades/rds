<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models;

use Yii;
use yii\db\ActiveQuery;
use whotrades\rds\components\ActiveRecord;
use whotrades\rds\helpers\MigrationInterface as MigrationHelperInterface;

/**
 * This is the model class for migrations in general.
 *
 * @property string             $obj_id
 * @property string             $obj_created
 * @property string             $obj_modified
 * @property integer            $obj_status_did
 * @property string             $migration_name
 * @property int                $migration_type
 * @property int                $migration_project_obj_id
 * @property int                $migration_release_request_obj_id
 * @property string             $migration_ticket
 * @property string             $migration_log
 *
 * @property Project            $project
 * @property ReleaseRequest     $releaseRequest
 */
abstract class MigrationBase extends ActiveRecord
{
    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'ID',
            'obj_created' => 'Created',
            'obj_modified' => 'Modified',
            'obj_status_did' => 'Status',
            'migration_type' => 'Migration Type',
            'migration_name' => 'Migration',
            'migration_ticket' => 'Migration Ticket',
            'migration_project_obj_id' => 'ID проекта',
            'migration_release_request_obj_id' => 'ID ReleaseRequest',
            'project.project_name' => 'Project',
            'releaseRequest.rr_build_version' => 'Release Request',
        );
    }

    /**
     * @return ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['obj_id' => 'migration_project_obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getReleaseRequest()
    {
        return $this->hasOne(ReleaseRequest::class, ['obj_id' => 'migration_release_request_obj_id'])->orWhere(['IS NOT', 'obj_status_did', null]);
    }

    /**
     * @return string
     */
    public function getNameForUrl()
    {
        return str_replace('\\', '/', $this->migration_name);
    }

    /**
     * @param string[] $migrationNameList
     * @param string $typeName
     * @param int $statusId
     * @param Project $project
     * @param ReleaseRequest $releaseRequest
     *
     * @return void
     */
    public static function createOrUpdateList(array $migrationNameList, $typeName, $statusId, Project $project, ReleaseRequest $releaseRequest)
    {
        foreach ($migrationNameList as $migrationName) {
            static::createOrUpdate($migrationName, $typeName, $statusId, $project, $releaseRequest);
        }
    }

    /**
     * @param string $migrationName
     * @param string $typeName
     * @param int $statusId
     * @param Project $project
     * @param ReleaseRequest $releaseRequest
     *
     * @return void
     */
    abstract public static function createOrUpdate($migrationName, $typeName, $statusId, Project $project, ReleaseRequest $releaseRequest);

    /**
     * @return string
     */
    abstract public function getStatusName();

    /**
     * @return string
     */
    abstract public function getTypeName();

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function fillFromGit()
    {
        $this->getMigrationHelper()->fillFromGit($this);
    }

    /**
     * @param int | string $status
     *
     * @throws \Exception
     */
    abstract public function tryUpdateStatus($status);

    /**
     * @param int | string $status
     *
     * @throws \Exception
     */
    abstract public function updateStatus($status);

    /**
     * @param string $jiraTicket
     *
     * @throws \Exception
     */
    public function updateJiraTicket($jiraTicket)
    {
        $this->migration_ticket = $jiraTicket;
        $this->save();
    }

    /**
     * @param string $log
     *
     * @throws \Exception
     */
    public function updateLog($log)
    {
        $this->migration_log .= $log;
        $this->save();
    }

    /**
     * @return MigrationHelperInterface
     */
    private function getMigrationHelper()
    {
        return new Yii::$app->params['migrationHelperClass'];
    }
}
