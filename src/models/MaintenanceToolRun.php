<?php

/**
 * This is the model class for table "rds.maintenance_tool_run".
 *
 * The followings are the available columns in table 'rds.maintenance_tool_run':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $mtr_maintenance_tool_obj_id
 * @property string $mtr_runner_user
 * @property string $mtr_pid
 * @property string $mtr_status
 * @property string $mtr_log
 *
 * The followings are the available model relations:
 * @property MaintenanceTool $mtrMaintenanceTool
 */
class MaintenanceToolRun extends CActiveRecord
{
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in-progress';
    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';

    private $progressPercent = false;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.maintenance_tool_run';
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
            array('obj_created, obj_modified, mtr_maintenance_tool_obj_id, mtr_runner_user, mtr_status', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('mtr_runner_user', 'length', 'max'=>256),
            array('mtr_log, mtr_pid', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, mtr_maintenance_tool_obj_id, mtr_runner_user, mtr_pid, mtr_status, mtr_log', 'safe', 'on'=>'search'),
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
            'mtrMaintenanceTool' => array(self::BELONGS_TO, 'MaintenanceTool', 'mtr_maintenance_tool_obj_id'),
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
            'obj_status_did' => 'Status Did',
            'mtr_maintenance_tool_obj_id' => 'Maintenance Tool',
            'mtr_runner_user' => 'Runner User',
            'mtr_pid' => 'PID',
            'mtr_log' => 'Log',
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

        $criteria->order = 't.obj_created desc';

        $criteria->compare('obj_id', $this->obj_id,true);
        $criteria->compare('obj_created', $this->obj_created,true);
        $criteria->compare('obj_modified', $this->obj_modified,true);
        $criteria->compare('obj_status_did', $this->obj_status_did);
        $criteria->compare('mtr_maintenance_tool_obj_id', $this->mtr_maintenance_tool_obj_id);
        $criteria->compare('mtr_runner_user', $this->mtr_runner_user,true);
        $criteria->compare('mtr_pid', $this->mtr_pid);
        $criteria->compare('mtr_status', $this->mtr_status);
        $criteria->compare('mtr_log', $this->mtr_log,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return MaintenanceToolRun the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Возвращает процент выполнения тула на основании предыдущего успешного запуска
     * Функция анализирует лог выполнения тула и пытается по логу определить процент выполнения
     *
     * @return int|null 0..100
     */
    public function getProgressPercentAndKey()
    {
        if ($this->progressPercent !== false) {
            return $this->progressPercent;
        }

        $c = new CDbCriteria();
        $c->compare('mtr_maintenance_tool_obj_id', $this->mtr_maintenance_tool_obj_id);
        $c->compare('mtr_status', MaintenanceToolRun::STATUS_DONE);
        $c->order = 'obj_id desc';
        $c->limit = 1;
        /** @var $lastSuccessBefore MaintenanceToolRun */
        $lastSuccessBefore = self::model()->find($c);
        //var_dump($lastSuccessBefore->attributes);
        if (empty($lastSuccessBefore)) {
            return $this->progressPercent = null;
        }

        $lines = array_reverse(explode("\n", $this->mtr_log));
        foreach ($lines as $line) {
            if (!$pair = $this->explodeLineByTimeAndMessage($line)) {
                continue;
            }
            list(, $message) = $pair;
            if (!$message) {
                continue;
            }

            $regex = '~\[([^\]]+)\]\s*'.preg_quote($message).'\s*~';
            if (!preg_match_all($regex, $lastSuccessBefore->mtr_log, $ans)) {
                continue;
            }

            if (count($ans[0]) > 1) {
                continue;
            }

            $time = $ans[1][0];

            $linesBefore = explode("\n", $lastSuccessBefore->mtr_log);
            $firstTime = $this->explodeLineByTimeAndMessage(reset($linesBefore))[0];
            $lastTime = $this->explodeLineByTimeAndMessage($linesBefore[count($linesBefore)-2])[0];

            $percent = (strtotime($time) - strtotime($firstTime)) / (strtotime($lastTime) - strtotime($firstTime));

            return [$this->progressPercent = 100 * $percent, $message];
        }

        return $this->progressPercent = null;
    }

    private function explodeLineByTimeAndMessage($line)
    {
        if (!preg_match('~^\[([^\]]+)\]\s*(.*)$~', $line, $ans)) {
            return null;
        }

        return [$ans[1], $ans[2]];
    }

    public function isInProgress()
    {
        return $this->mtr_status == self::STATUS_IN_PROGRESS;
    }
}