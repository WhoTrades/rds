<?php

/**
 * This is the model class for table "rds.build".
 *
 * The followings are the available columns in table 'rds.build':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property integer $build_release_request_obj_id
 * @property integer $build_worker_obj_id
 * @property integer $build_project_obj_id
 * @property string $build_status
 * @property string $build_attach
 * @property string $build_time_log
 *
 * The followings are the available model relations:
 * @property Worker $worker
 * @property Project $project
 * @property ReleaseRequest $releaseRequest
 */
class Build extends CActiveRecord
{
    const STATUS_NEW = 'new';
    const STATUS_BUILDING = 'building';
    const STATUS_BUILT = 'built';
    const STATUS_INSTALLED = 'installed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_USED = 'used';
    const STATUS_PREPROD_USING = 'preprod_using';
    const STATUS_PREPROD_MIGRATIONS = 'preprod_migrations';

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'rds.build';
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
			array('obj_created, obj_modified, build_worker_obj_id', 'required'),
			array('obj_status_did', 'numerical', 'integerOnly'=>true),
			array('build_status', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('obj_id, obj_created, obj_modified, obj_status_did, build_worker_obj_id, build_status, build_attach', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'worker' => array(self::BELONGS_TO, 'Worker', 'build_worker_obj_id'),
			'project' => array(self::BELONGS_TO, 'Project', 'build_project_obj_id'),
			'releaseRequest' => array(self::BELONGS_TO, 'ReleaseRequest', 'build_release_request_obj_id'),
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
			'build_worker_obj_id' => 'Worker ID',
			'build_status' => 'Status',
			'build_attach' => 'Attach',
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

		$criteria->compare('obj_id',$this->obj_id);
		$criteria->compare('obj_created',$this->obj_created,true);
		$criteria->compare('obj_modified',$this->obj_modified,true);
		$criteria->compare('obj_status_did',$this->obj_status_did);
		$criteria->compare('build_worker_obj_id',$this->build_worker_obj_id);
		$criteria->compare('build_status',$this->build_status,true);
		$criteria->compare('build_attach',$this->build_attach,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return build the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    public function getProgressbarInfo()
    {
        $c = new CDbCriteria();
        $c->compare('build_status', [Build::STATUS_INSTALLED, Build::STATUS_USED]);
        $c->compare('build_project_obj_id', $this->build_project_obj_id);
        $c->compare('build_worker_obj_id', $this->build_worker_obj_id);
        $c->order = 'obj_id desc';
        $prev = \Build::model()->find($c);


        $dataCurrent = array_reverse(array_keys(json_decode($this->build_time_log, true)));

        $data = json_decode($prev->build_time_log, true);
        if (!$data) {
            return null;
        }
        $lastPrev = end($data);

        $currentTime = 0;
        $currentKey = '';
        foreach ($dataCurrent as $currentKey) {
            if (isset($data[$currentKey])) {
                $currentTime = $data[$currentKey];
                break;
            }
        }

        $percent = 100*$currentTime/$lastPrev;

        return [$percent, $currentKey];
    }

    public static function getInstallingStatuses()
    {
        return [self::STATUS_BUILDING, self::STATUS_BUILT, self::STATUS_PREPROD_USING, self::STATUS_PREPROD_MIGRATIONS];
    }
}
