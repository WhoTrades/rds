<?php

/**
 * This is the model class for table "rds.maintenance_tool".
 *
 * The followings are the available columns in table 'rds.maintenance_tool':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $mt_name
 * @property string $mt_environment
 * @property string $mt_command
 * @property MaintenanceToolRun $lastRun
 *
 * The followings are the available model relations:
 * @property MaintenanceToolRun[] $maintenanceToolRuns
 */
class MaintenanceTool extends CActiveRecord
{
    /** @var MaintenanceToolRun */
    protected $lastRunLocal = false;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.maintenance_tool';
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
            array('obj_created, obj_modified, mt_name, mt_command', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('mt_name, mt_command', 'length', 'max'=>256),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, mt_name, mt_command', 'safe', 'on'=>'search'),
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
            'maintenanceToolRuns' => array(self::HAS_MANY, 'MaintenanceToolRun', 'mtr_maintenance_tool_obj_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'ID',
            'obj_created' => 'Created',
            'obj_modified' => 'Modified',
            'obj_status_did' => 'Status',
            'mt_name' => 'Name',
            'mt_command' => 'Command',
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
        $criteria=new CDbCriteria;

        $criteria->compare('obj_id',$this->obj_id);
        $criteria->compare('obj_created',$this->obj_created,true);
        $criteria->compare('obj_modified',$this->obj_modified,true);
        $criteria->compare('obj_status_did',$this->obj_status_did);
        $criteria->compare('mt_name',$this->mt_name,true);
        $criteria->compare('mt_command',$this->mt_command,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return MaintenanceTool the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function getLastRun()
    {
        if ($this->lastRunLocal !== false) {
            return $this->lastRunLocal;
        }

        $c = new CDbCriteria();
        $c->compare('mtr_maintenance_tool_obj_id', $this->obj_id);
        $c->order = 'obj_id desc';
        $c->limit = 1;

        return $this->lastRunLocal = MaintenanceToolRun::model()->find($c);
    }

    public function canBeStarted()
    {
        return 0 == MaintenanceToolRun::model()->countByAttributes([
            'mtr_maintenance_tool_obj_id' => $this->obj_id,
            'mtr_status' => [MaintenanceToolRun::STATUS_IN_PROGRESS, MaintenanceToolRun::STATUS_NEW],
        ]);
    }

    public function canBeKilled()
    {
        return !$this->canBeStarted();
    }

    public function getTitle()
    {
        return $this->mt_name." (".$this->mt_command.")";
    }

    /***
     * @return MaintenanceToolRun
     * @throws Exception
     */
    public function start($user)
    {
        Log::createLogMessage("Запущен тул {$this->getTitle()}", $user);

        if (!$this->canBeStarted()) {
            throw new Exception("Invalid tool status");
        }

        $mtr = new MaintenanceToolRun();

        $mtr->attributes = [
            'mtr_maintenance_tool_obj_id' => $this->obj_id,
            'mtr_runner_user' => $user,
            'mtr_status' => MaintenanceToolRun::STATUS_NEW,
        ];

        if ($mtr->save()) {
            $messageModel = (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel($this->mt_environment);
            $messageModel->sendMaintenanceToolStart(new \RdsSystem\Message\MaintenanceTool\Start($mtr->obj_id, $this->mt_command));
        }

        return $mtr;
    }

    public function stop($user)
    {
        /** @var $mtr MaintenanceToolRun */
        $mtr = MaintenanceToolRun::model()->findByAttributes([
            'mtr_maintenance_tool_obj_id' => $this->obj_id,
            'mtr_status' => [MaintenanceToolRun::STATUS_IN_PROGRESS],
        ]);

        if (!$mtr) {
            throw new Exception("Can't stop tool, it's not running");
        }

        Log::createLogMessage("Остановлен тул {$this->getTitle()}", $user);

        $messageModel = (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel($this->mt_environment);
        $messageModel->sendUnixSignalToGroup(new \RdsSystem\Message\UnixSignalToGroup(
            $mtr->mtr_pid
        ));
    }
}