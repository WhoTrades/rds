<?php
namespace app\models;

use app\components\ActiveRecord;

/**
 * This is the model class for table "rds.project_config_history".
 *
 * The followings are the available columns in table 'rds.project_config_history':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $pch_project_obj_id
 * @property string $pch_user
 * @property string $pch_config
 * @property string $pch_filename
 *
 * The followings are the available model relations:
 * @property Project $project
 */
class ProjectConfigHistory extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.project_config_history';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['obj_created', 'obj_modified', 'pch_project_obj_id'], 'required'),
            array(['obj_status_did'], 'number', 'integerOnly' => true),
            array(['pch_user'], 'string', 'max' => 128),
            array(['pch_config'], 'safe'),
        );
    }

    public function getProject()
    {
        return Project::findByPk($this->pch_project_obj_id);
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
            'pch_project_obj_id' => 'Pch Project Obj',
            'pch_user' => 'Pch User',
            'pch_config' => 'Pch Config',
        );
    }
}
