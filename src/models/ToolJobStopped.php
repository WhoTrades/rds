<?php
namespace app\models;

use app\components\ActiveRecord;

/**
 * This is the model class for table "cronjobs.tool_job_stopped".
 *
 * The followings are the available columns in table 'cronjobs.tool_job_stopped':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $project_obj_id
 * @property string $key
 * @property string $stopped_till
 *
 * The followings are the available model relations:
 * @property Project $projectObj
 */
class ToolJobStopped extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'cronjobs.tool_job_stopped';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['project_obj_id', 'key', 'stopped_till'], 'required'),
            array(['obj_status_did'], 'number'),
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
            'project_obj_id' => 'Project Obj',
            'key' => 'Key',
            'stopped_till' => 'Stopped Till',
        );
    }
}
