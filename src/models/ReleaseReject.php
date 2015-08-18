<?php

/**
 * This is the model class for table "rds.release_reject".
 *
 * The followings are the available columns in table 'rds.release_reject':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $rr_user
 * @property string $rr_comment
 * @property string $rr_project_obj_id
 * @property string $rr_release_version
 */
class ReleaseReject extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'rds.release_reject';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('obj_created, obj_modified, rr_user, rr_comment, rr_project_obj_id, rr_release_version', 'required'),
			array('obj_status_did', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('obj_id, obj_created, obj_modified, obj_status_did, rr_user, rr_comment, rr_project_obj_id rr_release_version', 'safe', 'on'=>'search'),
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
			'rr_project_obj_id' => 'Project ID',
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
		$criteria->compare('rr_user',$this->rr_user,true);
		$criteria->compare('rr_comment',$this->rr_comment,true);
		$criteria->compare('rr_project_obj_id',$this->rr_project_obj_id);
		$criteria->compare('rr_release_version',$this->rr_release_version);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ReleaseReject the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    public function getTitle()
    {
        return "запрет релиза #$this->obj_id {$this->project->project_name} v.$this->rr_release_version ($this->rr_comment)";
    }
}
