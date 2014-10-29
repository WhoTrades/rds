<?php

/**
 * This is the model class for table "rds.alert_log".
 *
 * The followings are the available columns in table 'rds.alert_log':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $alert_name
 * @property string $alert_status
 * @property string $alert_text
 */
class AlertLog extends CActiveRecord
{
    const WTS_LAMP_NAME = 'red_lamp_wts';

    const STATUS_OK = 'ok';
    const STATUS_ERROR = 'error';
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.alert_log';
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
            array('obj_created, obj_modified, alert_name, alert_status, alert_version', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('alert_name, alert_status', 'length', 'max'=>16),
            array('alert_text', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, alert_name, alert_status, alert_text, alert_version', 'safe', 'on'=>'search'),
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
            'alert_name' => 'Alert Name',
            'alert_status' => 'Alert Status',
            'alert_text' => 'Text',
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
        $criteria->compare('alert_name',$this->alert_name,true);
        $criteria->compare('alert_status',$this->alert_status,true);
        $criteria->compare('alert_text',$this->alert_text,true);
        $criteria->compare('alert_version',$this->alert_version);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return AlertLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
