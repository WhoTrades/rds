<?php
namespace app\models;

use app\components\ActiveRecord;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "rds.hard_migration".
 *
 * The followings are the available columns in table 'rds.hard_migration':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $migration_release_request_obj_id
 * @property string $migration_type
 * @property string $migration_name
 * @property string $migration_ticket
 * @property string $migration_status
 * @property string $migration_retry_count
 * @property double $migration_progress
 * @property string $migration_progress_action
 * @property string $migration_log
 * @property string $migration_pid
 * @property string $migration_project_obj_id
 * @property Project $project
 * @property string $migration_environment
 *
 * The followings are the available model relations:
 * @property ReleaseRequest $releaseRequest
 */
class HardMigration extends ActiveRecord
{
    const MIGRATION_STATUS_NEW          = 'new';
    const MIGRATION_STATUS_IN_PROGRESS  = 'process';
    const MIGRATION_STATUS_DONE         = 'done';
    const MIGRATION_STATUS_FAILED       = 'failed';
    const MIGRATION_STATUS_PAUSED       = 'paused';
    const MIGRATION_STATUS_STOPPED      = 'stopped';
    const MIGRATION_STATUS_STARTED      = 'started';


    //an: Эти свойства используются только для поиска
    public $build_version;

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.hard_migration';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['migration_type', 'migration_name', 'migration_environment'], 'required'),
            array(['obj_status_did'], 'number'),
            array(['migration_progress'], 'number'),
            array(['migration_name'], 'checkMigrationNameIsUnique'),
            array(['migration_type, migration_ticket, migration_status'], 'string', 'max' => 16),
            array(['migration_name, migration_progress_action'], 'string', 'max' => 255),
            array(['migration_release_request_obj_id, migration_project_obj_id, migration_retry_count'], 'safe'),
            array(
                [
                    'obj_id, obj_created, obj_modified, obj_status_did, migration_release_request_obj_id, migration_project_obj_id,
                    migration_type, migration_name, migration_ticket, migration_status, migration_retry_count, migration_progress,
                    migration_progress_action, project_obj_id, build_version, migration_environment',
                ],
                'safe',
                'on' => 'search',
            ),
        );
    }

    public function checkMigrationNameIsUnique($attribute, $params)
    {
        $query = static::find()->where([
            'migration_name' => $this->migration_name,
            'migration_environment' => $this->migration_environment
        ]);

        if ($this->obj_id) {
            $query->andWhere(['<>', 'obj_id', $this->obj_id]);
        }

        if ($query->one()) {
            $this->addError($attribute, "Migration $this->migration_name:$this->migration_environment already exists in DB");
        }
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['obj_id' => 'migration_project_obj_id']);
    }

    /**
     * @return ReleaseRequest
     */
    public function getReleaseRequest()
    {
        return $this->hasOne(ReleaseRequest::className(), ['obj_id' => 'migration_release_request_obj_id']);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'Obj',
            'obj_created' => 'Obj Created',
            'obj_modified' => 'Obj Modified',
            'obj_status_did' => 'Obj Status Did',
            'migration_release_request_obj_id' => 'Release Request ID',
            'migration_project_obj_id' => 'Project',
            'migration_type' => 'Migration Type',
            'migration_name' => 'Migration Name',
            'migration_ticket' => 'Migration Ticket',
            'migration_status' => 'Migration Status',
            'migration_retry_count' => 'Migration Retry Count',
            'migration_progress' => 'Migration Progress',
            'migration_progress_action' => 'Migration Progress Action',
        );
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = self::find()->filterWhere($params)->with('releaseRequest');

        if (empty($params['HardMigration_sort'])) {
            $query->orderBy('obj_created desc');
        }

        $dataProvider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 100]]);
        $this->load($params, 'search');

        return $dataProvider;
    }

    public function getNotDoneMigrationCountForTicket($ticket)
    {
        return static::find()->where(['migration_ticket' => $ticket])->andWhere([
            '<>', 'migration_status', self::MIGRATION_STATUS_DONE
        ])->count();
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return HardMigration the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function canBeStarted()
    {
        return in_array($this->migration_status, [HardMigration::MIGRATION_STATUS_NEW, HardMigration::MIGRATION_STATUS_STOPPED]) && $this->doesMigrationReleased();
    }

    public function canBeResumed()
    {
        return in_array($this->migration_status, [HardMigration::MIGRATION_STATUS_PAUSED]);
    }

    public function canBeStopped()
    {
        return in_array($this->migration_status, [HardMigration::MIGRATION_STATUS_IN_PROGRESS]);
    }

    public function canBePaused()
    {
        return in_array($this->migration_status, [HardMigration::MIGRATION_STATUS_IN_PROGRESS]);
    }

    public function canBeRestarted()
    {
        return in_array($this->migration_status, [HardMigration::MIGRATION_STATUS_FAILED]) && $this->doesMigrationReleased();
    }

    public function doesMigrationReleased()
    {
        return empty($this->releaseRequest) || $this->project->project_current_version >= $this->releaseRequest->rr_build_version;
    }

    public static function getAllStatuses()
    {
        return $map = array(
            HardMigration::MIGRATION_STATUS_NEW,
            HardMigration::MIGRATION_STATUS_IN_PROGRESS,
            HardMigration::MIGRATION_STATUS_STARTED,
            HardMigration::MIGRATION_STATUS_DONE,
            HardMigration::MIGRATION_STATUS_FAILED,
            HardMigration::MIGRATION_STATUS_PAUSED,
            HardMigration::MIGRATION_STATUS_STOPPED,
        );
    }

    public function getTitle()
    {
        return "Миграция $this->migration_name, окружение $this->migration_environment";
    }
}
