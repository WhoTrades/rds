<?php
/**
 * Base class for migration models
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models;

use yii\db\ActiveQuery;
use whotrades\rds\components\ActiveRecord;

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
    const TYPE_ID_PRE  = 1;
    const TYPE_ID_POST = 2;
    const TYPE_ID_HARD = 3;

    const TYPE_PRE  = 'pre';
    const TYPE_POST = 'post';
    const TYPE_HARD = 'hard';

    /**
     * @return string[]
     */
    public static function getTypeIdToNameMap()
    {
        return [
            self::TYPE_ID_PRE  => self::TYPE_PRE,
            self::TYPE_ID_POST => self::TYPE_POST,
            self::TYPE_ID_HARD => self::TYPE_HARD,
        ];
    }

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
     * @param string $migrationName
     * @param string $typeName
     * @param Project $project
     * @param ReleaseRequest $releaseRequest
     *
     * @return static
     */
    abstract public static function upsert($migrationName, $typeName, Project $project, ReleaseRequest $releaseRequest);

    /**
     * @return string
     */
    abstract public function getStatusName();

    /**
     * @return string
     */
    abstract public function getTypeName();

    /**
     * @return self[]
     */
    abstract protected static function getMigrationReadyBeAutoAppliedList();

    /**
     * @return static[]
     */
    public static function getMigrationCanBeAutoAppliedList()
    {
        $migrationListForApplying = static::getMigrationReadyBeAutoAppliedList();

        return array_filter($migrationListForApplying, function (self $migration) {
            return $migration->canBeApplied();
        });
    }

    /**
     * @return bool
     */
    abstract public function canBeApplied();

    /**
     * @return bool
     */
    abstract public function canBeAutoApplied();

    /**
     * @return void
     */
    abstract public function apply();

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
}
