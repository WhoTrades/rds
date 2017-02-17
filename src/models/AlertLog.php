<?php
namespace app\models;

use app\components\ActiveRecord;

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
 */
class AlertLog extends ActiveRecord
{
    const WTS_LAMP_NAME = 'red_lamp_wts';
    const TEAM_CITY_LAMP_NAME = 'red_lamp_team_city';
    const WTS_DEV_LAMP_NAME = 'red_lamp_wts_dev';
    const CRM_LAMP_NAME = 'crm_lamp';
    const STATUS_OK = 'ok';
    const STATUS_ERROR = 'error';
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.alert_log';
    }

    /**
     * Устанавливаем значения по-умолчанию
     */
    public function __construct()
    {
        if ($this->isNewRecord) {
            $this->alert_detect_at = date("r");
            $this->alert_ignore_timeout = date("r");
        }

        parent::__construct();
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['alert_detect_at', 'alert_lamp', 'alert_provider', 'alert_name', 'alert_status'], 'required'),
            array(['obj_status_did'], 'number'),
            array(['alert_lamp'], 'string', 'max' => 32),
            array('alert_provider', 'string', 'max' => 64),
            array(['alert_name'], 'string', 'max' => 256),
            array(['alert_text'], 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(
                ['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'alert_lamp', 'alert_provider', 'alert_name', 'alert_status', 'alert_text'],
                'safe',
                'on' => 'search',
            ),
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
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->alert_status = $status;
        if ($status == self::STATUS_ERROR) {
            $this->alert_detect_at = date('r');
        }
        $this->save();
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
