<?php

/**
 * This is the model class for table "rds.jira_notification_queue".
 *
 * The followings are the available columns in table 'rds.jira_notification_queue':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $jnq_project_obj_id
 * @property string $jnq_old_version
 * @property string $jnq_new_version
 *
 * The followings are the available model relations:
 * @property Project $project
 */
class JiraNotificationQueue extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.jira_notification_queue';
    }

    public function afterConstruct() {
        if ($this->isNewRecord) {
            $this->obj_created = date("r");
            $this->obj_modified = date("r");
        }
        return parent::afterConstruct();
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('obj_created, obj_modified, jnq_project_obj_id, jnq_old_version, jnq_new_version', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('jnq_old_version, jnq_new_version', 'length', 'max'=>32),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, jnq_project_obj_id, jnq_old_version, jnq_new_version', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'project' => array(self::BELONGS_TO, 'Project', 'jnq_project_obj_id'),
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
            'jnq_project_obj_id' => 'Jnq Project Obj',
            'jnq_old_version' => 'Jnq Old Version',
            'jnq_new_version' => 'Jnq New Version',
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
        $criteria->compare('jnq_project_obj_id',$this->jnq_project_obj_id,true);
        $criteria->compare('jnq_old_version',$this->jnq_old_version,true);
        $criteria->compare('jnq_new_version',$this->jnq_new_version,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return JiraNotificationQueue the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}