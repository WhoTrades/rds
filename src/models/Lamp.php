<?php
namespace app\models;

use app\components\ActiveRecord;

/**
 * This is the model class for table "rds.lamp".
 *
 * The followings are the available columns in table 'rds.lamp':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $lamp_name
 * @property string $lamp_timeout
 * @property string $lamp_receivers_list
 */
class Lamp extends ActiveRecord
{
    const ALERT_WAIT_TIMEOUT = '10 minutes';
    const ALERT_START_HOUR = 8;
    const ALERT_END_HOUR = 20;

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.lamp';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('obj_created, obj_modified, lamp_name', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly' => true),
            array('lamp_name', 'length', 'max' => 128),
            array('lamp_timeout, lamp_receivers_list', 'safe'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array();
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
            'lamp_name' => 'Lamp Name',
            'lamp_timeout' => 'Lamp Timeout',
            'lamp_receivers_list' => 'Lamp Receivers List',
        );
    }

    /**
     * @return AlertLog[]
     */
    public function getLampErrors()
    {
        $c = new CDbCriteria();
        $c->compare('alert_lamp', $this->lamp_name);
        $c->compare('alert_status', AlertLog::STATUS_ERROR);
        $c->compare('alert_ignore_timeout', '<' . date(DATE_ISO8601));

        $c->order = 'alert_detect_at DESC';

        $alertLog = AlertLog::model()->findAll($c);

        return $alertLog;
    }

    /**
     * @param string $lampName
     * @return Lamp
     */
    public function findByLampName($lampName)
    {
        return static::model()->findByAttributes(['lamp_name' => $lampName]);
    }

    /**
     * Список событий, которые игнорируются данной лампой
     *
     * @return AlertLog[]
     */
    public function getLampIgnores()
    {
        $c = new CDbCriteria();
        $c->compare('alert_lamp', $this->lamp_name);
        $c->compare('alert_ignore_timeout', '>' . date(DATE_ISO8601));

        $c->order = 'alert_ignore_timeout ASC';

        $alertLog = AlertLog::model()->findAll($c);

        return $alertLog;
    }

    /**
     * @param string $receiver
     * @return bool
     */
    public function isReceiverExists($receiver)
    {
        return in_array($receiver, json_decode($this->lamp_receivers_list));
    }

    /**
     * @param string $receiver
     */
    public function addReceiver($receiver)
    {
        $receivers = json_decode($this->lamp_receivers_list);
        $receivers[] = $receiver;
        $receivers = array_values(array_unique($receivers));
        $this->lamp_receivers_list = json_encode($receivers);
    }

    /**
     * @return string[]
     */
    public function getReceivers()
    {
        return json_decode($this->lamp_receivers_list);
    }

    /**
     * @param string $receiver
     */
    public function removeReceiver($receiver)
    {
        $receivers = json_decode($this->lamp_receivers_list);
        if (false !== $key = array_search($receiver, $receivers)) {
            unset($receivers[$key]);
            $receivers = array_values($receivers);
        }
        $this->lamp_receivers_list = json_encode($receivers);
    }

    /**
     * @return bool
     */
    public function getLampStatus()
    {
        $result = false;

        if ($this->lamp_timeout > date('Y-m-d H:i:s')) {
            return $result;
        }

        $errors = $this->getLampErrors();

        return !empty($errors);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Lamp the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}
