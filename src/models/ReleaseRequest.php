<?php

/**
 * This is the model class for table "rds.release_request".
 *
 * The followings are the available columns in table 'rds.release_request':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $rr_user
 * @property string $rr_comment
 * @property string $rr_project_obj_id
 * @property string $rr_status
 * @property string $rr_old_version
 * @property string $rr_build_version
 * @property string $rr_project_owner_code
 * @property string $rr_release_engineer_code
 * @property string $rr_project_owner_code_entered
 * @property string $rr_release_engineer_code_entered
 * @property Build[] $builds
 * @property Project $project
 * @property HardMigration[] $hardMigrations
 * @property string $rr_last_time_on_prod
 * @property string $rr_revert_after_time
 * @property string $rr_release_version
 * @property string $rr_new_migration_count
 * @property string $rr_new_migrations
 * @property string $rr_migration_status
 * @property string $rr_new_post_migrations
 * @property string $rr_post_migration_status
 * @property string $rr_built_time
 * @property string $rr_cron_config
 */
class ReleaseRequest extends CActiveRecord
{
    const IMMEDIATELY_TIME = 900;

    const STATUS_NEW                 = 'new';
    const STATUS_FAILED              = 'failed';
    const STATUS_INSTALLED           = 'installed';
    const STATUS_CODES               = 'codes';
    const STATUS_USING               = 'using';
    const STATUS_USED_ATTEMPT        = 'used_attempt';
    const STATUS_USED                = 'used';
    const STATUS_OLD                 = 'old';
    const STATUS_CANCELLING          = 'cancelling';
    const STATUS_CANCELLED           = 'cancelled';

    const MIGRATION_STATUS_NONE      = 'none';
    const MIGRATION_STATUS_UPDATING  = 'updating';
    const MIGRATION_STATUS_FAILED    = 'failed';
    const MIGRATION_STATUS_UP        = 'up';

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'rds.release_request';
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
			array('obj_created, obj_modified, rr_user, rr_comment, rr_project_obj_id, rr_build_version, rr_release_version', 'required'),
			array('obj_status_did', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('obj_id, obj_created, obj_modified, obj_status_did, rr_user, rr_comment, rr_project_obj_id, rr_build_version, rr_status', 'safe', 'on'=>'search'),
			array('rr_project_owner_code, rr_release_engineer_code', 'safe', 'on'=>'use'),
            array('rr_release_version', 'checkForReleaseReject'),
            array('rr_release_version', 'checkForTeamCityHasNoErrors'),
            array('rr_release_version', 'checkNotExistsHardMigrationOfPreviousRelease'),
		);
	}

    public function checkForReleaseReject($attribute, $params)
    {
        //an: Правило действует только для новых запросов на релиз
        if (!$this->isNewRecord) {
            return;
        }
        $rejects = ReleaseReject::model()->findAllByAttributes([
            'rr_project_obj_id' => $this->rr_project_obj_id,
            'rr_release_version' => $this->rr_release_version,
        ]);

        if ($rejects) {
            $messages = '';
            foreach ($rejects as $reject) {
                $messages[] = $reject->rr_comment." (".$reject->rr_user.")";
            }
            $this->addError($attribute, 'Запрет на релиз: '.implode("; ", $messages));
        }

    }

    public function checkForTeamCityHasNoErrors($attribute, $params)
    {
        //an: Правило действует только для новых запросов на релиз
        if (!$this->isNewRecord || !$this->rr_build_version || empty(Yii::app()->params['checkCiHasNoErrors'])) {
            return;
        }

        $c = new CDbCriteria();
        $c->compare('alert_version', $this->rr_release_version);
        $c->compare('alert_name', AlertLog::WTS_LAMP_NAME);
        $c->order = 'obj_id desc';
        $one = AlertLog::model()->find($c);
        if ($one && $one->alert_status == AlertLog::STATUS_ERROR) {
            foreach (explode(", ", $one->alert_text) as $url) {
                $this->addError($attribute, 'Ошибка сборки CI: <a href="'.$url.'">'.$url."</a>");
            }
        }
    }

    public function checkNotExistsHardMigrationOfPreviousRelease($attribute, $params)
    {
        $c = new CDbCriteria();
        $c->with = 'releaseRequest';
        $c->compare("rr_release_version", "<".$this->rr_release_version);
        $c->compare("migration_status", "<>".HardMigration::MIGRATION_STATUS_DONE);
        $c->compare("migration_project_obj_id", $this->rr_project_obj_id);
        $count = HardMigration::model()->count($c);
        if ($count) {
            $this->addError($attribute, "Есть невыполненные тяжелая миграции ($count шт) от предыдущего релиза: <a href='".Yii::app()->createUrl('hardMigration/index', [
                'HardMigration[build_version]' => '<='.$this->rr_release_version,
                'HardMigration[migration_status]' => '<>'.HardMigration::MIGRATION_STATUS_DONE,
                'HardMigration[migration_project_obj_id]' => $this->rr_project_obj_id,
            ])."' target='_blank'>Посмотреть</a>");
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
            'project' => array(self::BELONGS_TO, 'Project', 'rr_project_obj_id'),
            'builds' => array(self::HAS_MANY, 'Build', 'build_release_request_obj_id'),
            'hardMigrations' => array(self::HAS_MANY, 'HardMigration', 'migration_release_request_obj_id'),
		);
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
			'obj_status_did' => 'Status Did',
			'rr_user' => 'User',
			'rr_status' => 'Status',
			'rr_comment' => 'Comment',
			'rr_project_obj_id' => 'Project',
			'rr_build_version' => 'Build',
			'rr_project_owner_code' => 'Project owner code',
			'rr_release_engineer_code' => 'Release engineer code',
			'rr_release_version' => 'Release version',
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

		$criteria->compare('t.obj_id',$this->obj_id);
		$criteria->compare('t.obj_created',$this->obj_created,true);
		$criteria->compare('t.obj_modified',$this->obj_modified,true);
		$criteria->compare('t.obj_status_did',$this->obj_status_did);
		$criteria->compare('t.rr_user',$this->rr_user,true);
		$criteria->compare('t.rr_status',$this->rr_status,true);
		$criteria->compare('t.rr_comment',$this->rr_comment,true);
		$criteria->compare('t.rr_project_obj_id',$this->rr_project_obj_id);
		$criteria->compare('t.rr_build_version',$this->rr_build_version, true);
        $criteria->order = 't.obj_created desc';
        $criteria->with = array('builds', 'builds.worker', 'builds.project');

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

    public function countNotFinishedBuilds()
    {
        $c = new CDbCriteria();
        $c->compare('build_release_request_obj_id', $this->obj_id);
        $c->compare('build_status', '<>'.Build::STATUS_INSTALLED);
        return Build::model()->count($c);
    }

    /** @return ReleaseRequest|null */
    public function getOldReleaseRequest()
    {
        return self::model()->findByAttributes(array(
            'rr_build_version' => $this->rr_old_version,
            'rr_project_obj_id' => $this->rr_project_obj_id,
        ));
    }

    /** @return ReleaseRequest|null */
    public function getUsedReleaseRequest()
    {
        return self::model()->findByAttributes(array(
            'rr_status' => self::STATUS_USED,
            'rr_project_obj_id' => $this->rr_project_obj_id,
        ));
    }

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ReleaseRequest the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    public function canBeUsed()
    {
        return in_array($this->rr_status, array(self::STATUS_INSTALLED, self::STATUS_OLD));
    }

    public function canByUsedImmediately()
    {
        return !empty(Yii::app()->params['useImmediately']) || (in_array($this->rr_status, array(self::STATUS_OLD)) && (time() - $this->getLastTimeOnProdTimestamp() < self::IMMEDIATELY_TIME));
    }

    public function getLastTimeOnProdTimestamp()
    {
        if ($this->rr_status == self::STATUS_USED || $this->rr_status == self::STATUS_USED_ATTEMPT) {
            return time();
        }

        return $this->rr_last_time_on_prod ? strtotime($this->rr_last_time_on_prod) : 0;
    }

    public function getTitle()
    {
        return "Запрос релиза #$this->obj_id {$this->project->project_name}::{$this->rr_build_version} ($this->rr_comment)";
    }

    public function isInstalledStatus()
    {
        return in_array($this->rr_status, self::getInstalledStatuses());
    }

    public function isUsedStatus()
    {
        return in_array($this->rr_status, self::getUsedStatuses());
    }

    public static function getInstalledStatuses()
    {
        return [self::STATUS_INSTALLED, self::STATUS_OLD, self::STATUS_USED, self::STATUS_USED_ATTEMPT];
    }

    public static function getUsedStatuses()
    {
        return [self::STATUS_USED, self::STATUS_USED_ATTEMPT];
    }

    public function getBuildTag()
    {
        return $this->project->project_name."-".$this->rr_build_version;
    }
}
