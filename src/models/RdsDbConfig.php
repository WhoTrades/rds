<?php

/**
 * This is the model class for table "rds.rds_db_config".
 *
 * The followings are the available columns in table 'rds.rds_db_config':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $is_tst_updating_enabled
 * @property string $red_lamp_wts_timeout
 * @property string $red_lamp_team_city_timeout
 * @property string $red_lamp_phplogs_dev_timeout
 * @property string $crm_lamp_timeout
 * @property string $preprod_online
 * @property string $cpu_usage_last_truncate
 * @property string $deployment_enabled
 * @property string $deployment_enabled_reason
 */
class RdsDbConfig extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.rds_db_config';
    }

    /** @return RdsDbConfig */
    public static function get()
    {
        return self::model()->find();
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('obj_created, obj_modified', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly' => true),
            array('preprod_online, is_tst_updating_enabled', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, is_tst_updating_enabled,cpu_usage_last_truncate', 'safe', 'on' => 'search'),
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
            'is_tst_updating_enabled' => 'Включено ли обновление tst контура',
            'cpu_usage_last_truncate' => 'Cpu Usage last truncate',
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
        $criteria->compare('is_tst_updating_enabled',$this->is_tst_updating_enabled);
        $criteria->compare('cpu_usage_last_truncate',$this->cpu_usage_last_truncate,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return RdsDbConfig the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
