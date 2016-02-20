<?php
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
 */
class ProjectConfig extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.project_config';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['obj_created, obj_modified, pc_project_obj_id, pc_filename', 'required'],
            ['obj_status_did', 'numerical', 'integerOnly' => true],
            ['pc_filename', 'length', 'max' => 128],
            ['pc_content', 'ext.validators.PhpSyntaxValidator'],
            ['obj_id, obj_created, obj_modified, obj_status_did, pc_project_obj_id, pc_filename, pc_content', 'safe', 'on' => 'search'],
        ];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            'project' => array(self::BELONGS_TO, 'Project', 'pc_project_obj_id'),
        ];
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
            'pc_filename' => 'Filename',
            'pc_content' => 'Content',
        ];
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        $criteria = new CDbCriteria();

        $criteria->compare('obj_id', $this->obj_id, true);
        $criteria->compare('obj_created', $this->obj_created, true);
        $criteria->compare('obj_modified', $this->obj_modified, true);
        $criteria->compare('obj_status_did', $this->obj_status_did);
        $criteria->compare('pc_project_obj_id', $this->pc_project_obj_id, true);
        $criteria->compare('pc_filename', $this->pc_filename, true);
        $criteria->compare('pc_content', $this->pc_content, true);

        return new CActiveDataProvider($this, ['criteria' => $criteria]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     *
     * @param string $className active record class name.
     *
     * @return ProjectConfig the static model class
     */
    public static function model($className = null)
    {
        $className = $className ?: __CLASS__;

        return parent::model($className);
    }
}
