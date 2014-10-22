<?php

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
class HardMigration extends CActiveRecord
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
    public function tableName()
    {
        return 'rds.hard_migration';
    }

    public function afterConstruct() {
        if ($this->isNewRecord) {
            $this->obj_created = date("r");
            $this->obj_modified = date("r");
        }
        return parent::afterConstruct();
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('obj_created, obj_modified, migration_type, migration_name, migration_environment', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('migration_progress', 'numerical'),
            array('migration_name', 'checkMigrationNameIsUnique'),
            array('migration_type, migration_ticket, migration_status', 'length', 'max'=>16),
            array('migration_name, migration_progress_action', 'length', 'max'=>255),
            array('migration_release_request_obj_id, migration_project_obj_id, migration_retry_count', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, migration_release_request_obj_id, migration_project_obj_id, migration_type, migration_name, migration_ticket, migration_status, migration_retry_count, migration_progress, migration_progress_action, project_obj_id, build_version, migration_environment', 'safe', 'on'=>'search'),
        );
    }

    public function checkMigrationNameIsUnique($attribute, $params)
    {
        $c = new CDbCriteria();
        $c->compare("migration_name", $this->migration_name);
        $c->compare("migration_environment", $this->migration_environment);
        if ($this->obj_id) {
            $c->compare("obj_id", '<>'.$this->obj_id);
        }
        $c->limit = 1;
        if (self::model()->find($c)) {
            $this->addError($attribute, "Migration $this->migration_name:$this->migration_environment already exists in DB");
        }
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'releaseRequest' => array(self::BELONGS_TO, 'ReleaseRequest', 'migration_release_request_obj_id'),
            'project' => array(self::BELONGS_TO, 'Project', 'migration_project_obj_id'),
        );
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
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;

        $criteria->with = ['releaseRequest'];

        $criteria->compare('t.obj_id',$this->obj_id);
        $criteria->compare('obj_created',$this->obj_created,true);
        $criteria->compare('obj_modified',$this->obj_modified,true);
        $criteria->compare('obj_status_did',$this->obj_status_did);
        $criteria->compare('migration_release_request_obj_id',$this->migration_release_request_obj_id);
        $criteria->compare('migration_project_obj_id',$this->migration_project_obj_id);
        $criteria->compare('migration_type',$this->migration_type,true);
        $criteria->compare('migration_name',$this->migration_name,true);
        $criteria->compare('migration_ticket',$this->migration_ticket,true);
        $criteria->compare('migration_status',$this->migration_status,true);
        $criteria->compare('migration_retry_count',$this->migration_retry_count,true);
        $criteria->compare('migration_progress',$this->migration_progress);
        $criteria->compare('migration_progress_action',$this->migration_progress_action,true);
        $criteria->compare('rr_build_version',$this->build_version, true);
        $criteria->compare('migration_environment',$this->migration_environment, true);

        if (empty($_GET['HardMigration_sort'])) {
            $criteria->order = 't.obj_created desc';
        }

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    public function getNotDoneMigrationCountForTicket($ticket)
    {
        return $this->countByAttributes([
            'migration_ticket' => $ticket,
            'migration_status' => '<>'.self::MIGRATION_STATUS_DONE,
        ]);
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
        return in_array($this->migration_status, [HardMigration::MIGRATION_STATUS_NEW, HardMigration::MIGRATION_STATUS_STOPPED]);
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
        return in_array($this->migration_status, [HardMigration::MIGRATION_STATUS_FAILED]);
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
}
