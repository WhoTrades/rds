<?php
namespace app\models;

use app\components\ActiveRecord;

/**
 * This is the model class for table "rds.project2worker".
 *
 * The followings are the available columns in table 'rds.project2worker':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $worker_obj_id
 * @property string $project_obj_id
 *
 * The followings are the available model relations:
 * @property Worker $worker
 * @property Project $project
 */
class Project2worker extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.project2worker';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['worker_obj_id', 'project_obj_id'], 'required'),
            array(['obj_status_did'], 'number'),
        );
    }

    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['obj_id' => 'worker_obj_id']);
    }

    public function getProject()
    {
        return $this->hasOne(Project::className(), ['obj_id' => 'project_obj_id']);
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
            'worker_obj_id' => 'Worket Obj',
            'project_obj_id' => 'Project Obj',
        );
    }

    public function getTitle()
    {
        return "Связка {$this->project->project_name} <-> {$this->worker->worker_name}";
    }
}
