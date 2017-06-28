<?php
namespace app\models;

use app\components\ActiveRecord;

/**
 * This is the model class for table "rds.project2project".
 *
 * The followings are the available columns in table 'rds.project2project':
 * @property integer $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property integer $parent_project_obj_id
 * @property integer $child_project_obj_id
 *
 * The followings are the available model relations:
 * @property Project $parent
 * @property Project $child
 */
class Project2Project extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.project2project';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['parent_project_obj_id', 'child_project_obj_id'], 'required'),
            array(['obj_status_did'], 'number'),
        );
    }

    /**
     * @return Project
     */
    public function getParent()
    {
        return Project::findOne(['obj_id' => $this->parent_project_obj_id]);
    }

    /**
     * @return Project
     */
    public function getChild()
    {
        return Project::findOne(['obj_id' => $this->child_project_obj_id]);
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
            'parent_project_obj_id' => 'Project Parent Obj',
            'child_project_obj_id' => 'Project Child Obj',
        );
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "Связка {$this->parent->project_name} <-> {$this->child->project_name}";
    }
}
