<?php

/**
 * This is the model class for table "rds.teamcity_build".
 *
 * The followings are the available columns in table 'rds.teamcity_build':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $tb_run_test_obj_id
 * @property string $tb_build_type_id
 * @property string $tb_branch
 * @property string $tb_status
 * @property string $tb_url
 * @property string $tb_notified
 *
 * The followings are the available model relations:
 * @property TeamcityRunTest $teamCityRunTest
 */
class TeamCityBuild extends CActiveRecord
{
    const STATUS_QUEUED = 'queued';
    const STATUS_FAILED = 'failed';
    const STATUS_SUCCESS = 'success';
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.teamcity_build';
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
                array('obj_created, obj_modified, tb_run_test_obj_id, tb_build_type_id, tb_branch, tb_status, tb_url', 'required'),
                array('obj_status_did', 'numerical', 'integerOnly'=>true),
                array('tb_build_type_id, tb_branch, tb_status', 'length', 'max'=>64),
                array('tb_url', 'length', 'max'=>128),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
                array('obj_id, obj_created, obj_modified, obj_status_did, tb_run_test_obj_id, tb_build_type_id, tb_branch, tb_status, tb_url, tb_notified', 'safe', 'on'=>'search'),
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
            'teamCityRunTest' => array(self::BELONGS_TO, 'TeamcityRunTest', 'tb_run_test_obj_id'),
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
                'tb_run_test_obj_id' => 'Tb Run Test Obj',
                'tb_build_type_id' => 'Tb Build Type',
                'tb_branch' => 'Tb Branch',
                'tb_status' => 'Tb Status',
                'tb_url' => 'Tb Url',
                'tb_notified' => 'Notified',
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

        $criteria->compare('obj_id',$this->obj_id,true);
        $criteria->compare('obj_created',$this->obj_created,true);
        $criteria->compare('obj_modified',$this->obj_modified,true);
        $criteria->compare('obj_status_did',$this->obj_status_did);
        $criteria->compare('tb_run_test_obj_id',$this->tb_run_test_obj_id,true);
        $criteria->compare('tb_build_type_id',$this->tb_build_type_id,true);
        $criteria->compare('tb_branch',$this->tb_branch,true);
        $criteria->compare('tb_status',$this->tb_status,true);
        $criteria->compare('tb_url',$this->tb_url,true);
        $criteria->compare('tb_notified',$this->tb_notified);

        return new CActiveDataProvider($this, array(
                'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TeamcityBuild the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function getQueuedId()
    {
        preg_match('~itemId=(\d+)~', $this->tb_url, $ans);

        return $ans[1];
    }
}