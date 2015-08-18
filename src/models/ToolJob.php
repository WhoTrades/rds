<?php

/**
 * This is the model class for table "cronjobs.tool_job".
 *
 * The followings are the available columns in table 'cronjobs.tool_job':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $project_obj_id
 * @property string $key
 * @property string $group
 * @property string $command
 * @property string $version
 * @property string $package
 *
 * The followings are the available model relations:
 * @property Project $project
 */
class ToolJob extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'cronjobs.tool_job';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('obj_created, obj_modified, project_obj_id, key, command, version', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('key', 'length', 'max'=>12),
            array('group', 'length', 'max'=>250),
            array('package', 'length', 'max'=>32),
            array('command', 'length', 'max'=>1000),
            array('version', 'length', 'max'=>16),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, project_obj_id, key, group, command, version', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'project' => array(self::BELONGS_TO, 'Project', 'project_obj_id'),
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
            'group' => 'Group',
            'command' => 'Command',
            'version' => 'Version',
            'package' => 'Package',
        );
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
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('obj_id',$this->obj_id,true);
        $criteria->compare('obj_created',$this->obj_created,true);
        $criteria->compare('obj_modified',$this->obj_modified,true);
        $criteria->compare('obj_status_did',$this->obj_status_did);
        $criteria->compare('project_obj_id',$this->project_obj_id,true);
        $criteria->compare('key',$this->key,true);
        $criteria->compare('group',$this->group,true);
        $criteria->compare('command',$this->command,true);
        $criteria->compare('version',$this->version,true);
        $criteria->compare('package',$this->package,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * @return ToolJobStopped|null
     */
    public function getToolJobStopped()
    {
        /** @var $stopped ToolJobStopped */
        $stopped = ToolJobStopped::model()->findByAttributes([
            'project_obj_id' => $this->project_obj_id,
            'key' => $this->key,
        ]);

        if (!$stopped) {
            return null;
        }

        if (strtotime($stopped->stopped_till) > time()) {
            return $stopped;
        }

        return null;
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ToolJob the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}