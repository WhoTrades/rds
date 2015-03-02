<?php

/**
 * This is the model class for table "rds.wtflow_stat".
 *
 * The followings are the available columns in table 'rds.wtflow_stat':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $developer_id
 * @property integer $exit_code
 * @property string $action
 * @property string $ticket
 * @property string $command
 * @property integer $time
 * @property string $log
 *
 * The followings are the available model relations:
 * @property Developer $developer
 */
class WtflowStat extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.wtflow_stat';
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
            array('obj_created, obj_modified, developer_id, exit_code, action, ticket, command, time', 'required'),
            array('obj_status_did, exit_code', 'numerical', 'integerOnly'=>true),
            array('action, ticket', 'length', 'max'=>50),
            array('command', 'length', 'max'=>250),
            array('log', 'safe'),
            array('time', 'numerical'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, developer_id, exit_code, action, ticket, command, time, log', 'safe', 'on'=>'search'),
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
            'developer' => array(self::BELONGS_TO, 'Developer', 'developer_id'),
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
            'developer_id' => 'Developer',
            'exit_code' => 'Exit Code',
            'action' => 'Action',
            'ticket' => 'Ticket',
            'command' => 'Command',
            'time' => 'Time',
            'log' => 'Log',
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
        $criteria->compare('developer_id',$this->developer_id,true);
        $criteria->compare('exit_code',$this->exit_code);
        $criteria->compare('action',$this->action,true);
        $criteria->compare('ticket',$this->ticket,true);
        $criteria->compare('command',$this->command,true);
        $criteria->compare('time',$this->time);
        $criteria->compare('log',$this->log,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return WtflowStat the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
