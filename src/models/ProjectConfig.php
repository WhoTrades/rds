<?php
namespace app\models;

use app\components\ActiveRecord;
use app\extensions\validators\PhpSyntaxValidator;

/**
 * @author Artem Naumenko
 *
 * This is the model class for table "rds.project_config".
 * The followings are the available columns in table 'rds.project_config':
 *
 * @property string  $obj_id
 * @property string  $obj_created
 * @property string  $obj_modified
 * @property integer $obj_status_did
 * @property string  $pc_project_obj_id
 * @property string  $pc_filename
 * @property string  $pc_content
 * @property Project $project
 */
class ProjectConfig extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.project_config';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            [['pc_project_obj_id', 'pc_filename'], 'required'],
            ['obj_status_did', 'number'],
            ['pc_filename', 'string', 'max' => 128],
            ['pc_content', PhpSyntaxValidator::class],
            [['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'pc_project_obj_id', 'pc_filename', 'pc_content'], 'safe', 'on' => 'search'],
        ];
    }

    /**
     * @return ProjectConfig
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['obj_id' => 'pc_project_obj_id']);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'obj_id' => 'Id',
            'obj_created' => 'Created',
            'obj_modified' => 'Modified',
            'obj_status_did' => 'status did',
            'pc_project_obj_id' => 'Project Id',
            'pc_filename' => 'Имя файла',
            'pc_content' => 'Содержимое',
        ];
    }
}
