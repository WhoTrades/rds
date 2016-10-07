<?php
/**
 * @package rds\zoho
 */

/**
 * @author Artem Naumenko
 *
 * This is the model class for table "rds.pgq_zoho_integration".
 *
 * The followings are the available columns in table 'rds.pgq_zoho_integration':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $task
 * @property string $data
 */
class PgqZohoIntegration extends ActiveRecord
{
    // an: Указание номера тикета jira в тикете zoho, при автоматическом создании последнего
    const TASK_NAME_SYNC_ZOHO_TITLE = 'TASK_NAME_SYNC_ZOHO_TITLE';
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.pgq_zoho_integration';
    }

    /**
     * Инициализация
     */
    public function init()
    {
        $this->obj_created = date("r");
        $this->obj_modified = date("r");
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('obj_created, obj_modified, task', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly' => true),
            array('task', 'length', 'max' => 64),
            array('data', 'safe'),
            // The following rule is used by search().
            array('obj_id, obj_created, obj_modified, obj_status_did, task, data', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'ID',
            'obj_created' => 'Date Created',
            'obj_modified' => 'Date Modified',
            'obj_status_did' => 'Status ID',
            'task' => 'Task name',
            'data' => 'Data json',
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
        $criteria = new CDbCriteria();

        $criteria->compare('obj_id', $this->obj_id);
        $criteria->compare('obj_created', $this->obj_created);
        $criteria->compare('obj_modified', $this->obj_modified);
        $criteria->compare('obj_status_did', $this->obj_status_did);
        $criteria->compare('task', $this->task, true);
        $criteria->compare('data', $this->data, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PgqZohoIntegration the static model class
     */
    public static function model($className = null)
    {
        return parent::model($className ?: __CLASS__);
    }
}
