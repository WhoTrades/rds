<?php
namespace app\models;

use app\components\ActiveRecord;

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
 */
class RdsDbConfig extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.rds_db_config';
    }

    /** @return RdsDbConfig */
    public static function get()
    {
        return self::find()->one();
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
            array('obj_status_did', 'number', 'integerOnly' => true),
            array(
                'red_lamp_wts_timeout, crm_lamp_timeout, red_lamp_team_city_timeout, red_lamp_phplogs_dev_timeout, '
                    . 'preprod_online, is_tst_updating_enabled',
                'safe',
            ),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(
                'obj_id, obj_created, obj_modified, crm_lamp_timeout, obj_status_did, is_tst_updating_enabled,'
                    . ' red_lamp_wts_timeout, red_lamp_team_city_timeout, red_lamp_phplogs_dev_timeout, '
                    . 'cpu_usage_last_truncate', 'safe',
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
            'is_tst_updating_enabled' => 'Включено ли обновление tst контура',
            'red_lamp_wts_timeout' => 'Red Lamp Wts Timeout',
            'red_lamp_team_city_timeout' => 'Red Lamp TeamCity Timeout',
            'red_lamp_phplogs_dev_timeout' => 'Red Lamp PhpLogsDEV Timeout',
            'crm_lamp_timeout' => 'CRM Lamp Timeout',
            'cpu_usage_last_truncate' => 'Cpu Usage last truncate',
        );
    }
}
