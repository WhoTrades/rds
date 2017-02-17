<?php
namespace app\models;

use app\components\ActiveRecord;

/**
 * This is the model class for table "cronjobs.cpu_usage".
 *
 * The followings are the available columns in table 'cronjobs.cpu_usage':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $project_name
 * @property string $key
 * @property double $cpu_time
 * @property string $last_run_time
 * @property int $last_exit_code
 * @property int $last_duration
 */
class CpuUsage extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'cronjobs.cpu_usage';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['project_name', 'key'], 'required'),
            array(['obj_status_did'], 'number', 'integerOnly' => true),
            array(['cpu_time'], 'number'),
            array(['project_name'], 'string', 'max' => 24),
            array(['key'], 'string', 'max' => 12),
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
            'project_name' => 'Project Name',
            'key' => 'Key',
            'cpu_time' => 'Cpu Time',
        );
    }
}
