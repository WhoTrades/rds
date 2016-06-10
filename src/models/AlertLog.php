<?php

/**
 * This is the model class for table "rds.alert_log".
 *
 * The followings are the available columns in table 'rds.alert_log':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $alert_detect_at
 * @property string $alert_lamp
 * @property string $alert_provider
 * @property string $alert_name
 * @property string $alert_status
 * @property string $alert_text
 * @property string $alert_ignore_timeout
 *
 * @method AlertLog[] findAll($condition = '', $params = array())
 * @method AlertLog findByPk($pk,$condition='',$params=array())
 */
class AlertLog extends ActiveRecord
{
    const WTS_LAMP_NAME = 'red_lamp_wts';
    const TEAM_CITY_LAMP_NAME = 'red_lamp_team_city';
    const PHPLOGS_DEV_LAMP_NAME = 'red_lamp_phplogs_dev';
    const CRM_LAMP_NAME = 'crm_lamp';
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
            $this->alert_detect_at = date("r");
            $this->alert_ignore_timeout = date("r");
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
            array('obj_created, obj_modified, alert_detect_at, alert_lamp, alert_provider, alert_name, alert_status', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('alert_lamp', 'length', 'max'=>32),
            array('alert_provider', 'length', 'max'=>64),
            array('alert_name', 'length', 'max'=>256),
            array('alert_text', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, alert_lamp, alert_provider, alert_name, alert_status, alert_text', 'safe', 'on'=>'search'),
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
            'alert_detect_at' => 'Detect at',
            'alert_lamp' => 'Lamp Name',
            'alert_provider' => 'Provider Name',
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
        $criteria->compare('alert_lamp',$this->alert_name,true);
        $criteria->compare('alert_provider',$this->alert_name,true);
        $criteria->compare('alert_name',$this->alert_name,true);
        $criteria->compare('alert_status',$this->alert_status,true);
        $criteria->compare('alert_text',$this->alert_text,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->alert_status = $status;
        if($status === self::STATUS_ERROR) {
            $this->alert_detect_at = date('r');
        }
        $this->save();
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

    /**
     * Имеется ли ссылка на страницу ошибки
     *
     * @return bool
     */
    public function hasLink()
    {
        return strpos($this->alert_text, 'url: ') !== false;
    }

    /**
     * Возвращает ссылку на страницу ошибки
     *
     * @return string
     */
    public function getLink()
    {
        return trim(str_replace('url: ', '', $this->alert_text));
    }
}
