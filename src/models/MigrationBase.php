<?php
/**
 * Base class for migration models
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models;

use Yii;
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
            'obj_id' => Yii::t('rds', 'id'),
            'obj_created' => Yii::t('rds', 'create_date'),
            'obj_modified' => Yii::t('rds', 'modify_date'),
            'obj_status_did' => Yii::t('rds', 'status'),
            'migration_type' => 'Migration Type',
            'migration_name' => 'Migration',
            'migration_ticket' => 'Migration Ticket',
            'migration_project_obj_id' => Yii::t('rds', 'project_id'),
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
     * @param string $typeName
     * @param Project $project
     * @param int $objIdFilter
     * @param int $limit
     *
     * @return MigrationBase[]
     */
    abstract public static function findNotDeletedWithLimit(string $typeName, Project $project, int $objIdFilter, int $limit): array;

    /**
     * @return ActiveQuery
     */
    public static function findWithoutLog(): ActiveQuery
    {
        return self::find()->select([
            'obj_id',
            'obj_created',
            'obj_modified',
            'obj_status_did',
            'migration_type',
            'migration_name',
            'migration_ticket',
            'migration_project_obj_id',
            'migration_release_request_obj_id',
        ]);
    }

    /**
     * @param string $migrationName
     * @param string $typeName
     * @param ReleaseRequest $releaseRequest
     *
     * @return static
     */
    abstract public static function upsert($migrationName, $typeName, ReleaseRequest $releaseRequest);

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
            return $migration->canBeAutoApplied();
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
     * @throws \Exception
     */
    abstract public function setStatusDeleted();

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
