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
 * @property Build[] builds
 */
class ReleaseRequest extends CActiveRecord
{
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
			array('obj_created, obj_modified, rr_user, rr_comment, rr_project_obj_id', 'required'),
			array('obj_status_did', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('obj_id, obj_created, obj_modified, obj_status_did, rr_user, rr_comment, rr_project_obj_id', 'safe', 'on'=>'search'),
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
            'project' => array(self::BELONGS_TO, 'Project', 'rr_project_obj_id'),
            'builds' => array(self::HAS_MANY, 'Build', 'build_release_request_obj_id'),
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
			'rr_comment' => 'Comment',
			'rr_project_obj_id' => 'Project',
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
		$criteria->compare('t.rr_comment',$this->rr_comment,true);
		$criteria->compare('t.rr_project_obj_id',$this->rr_project_obj_id);
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
}
