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
 * @property string $deployment_enabled
 * @property string $deployment_enabled_reason
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
}
