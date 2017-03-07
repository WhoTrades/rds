<?php
namespace app\models;

use app\components\ActiveRecord;
use yii\data\ActiveDataProvider;

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
class MaintenanceTool extends ActiveRecord
{
    /** @var MaintenanceToolRun */
    protected $lastRunLocal = false;

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.maintenance_tool';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['mt_name', 'mt_command'], 'required'),
            array(['obj_status_did'], 'number'),
            array(['mt_name', 'mt_command'], 'string', 'max' => 256),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'mt_name', 'mt_command'], 'safe', 'on' => 'search'),
        );
    }

    public function getMaintenanceToolRuns()
    {
        return $this->hasMany(MaintenanceToolRun::className(), ['mtr_maintenance_tool_obj_id' => 'obj_id']);
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = self::find()->andWhere(array_filter($params));
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $this->load($params, 'search');

        return $dataProvider;
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

    public function getLastRun()
    {
        if ($this->lastRunLocal !== false) {
            return $this->lastRunLocal;
        }

        return $this->lastRunLocal = MaintenanceToolRun::find()->where([
            'mtr_maintenance_tool_obj_id' => $this->obj_id,
        ])->orderBy('obj_id desc')->one();
    }

    public function canBeStarted()
    {
        return 0 == MaintenanceToolRun::find()->where([
            'mtr_maintenance_tool_obj_id' => $this->obj_id,
            'mtr_status' => [MaintenanceToolRun::STATUS_IN_PROGRESS, MaintenanceToolRun::STATUS_NEW],
        ])->count();
    }

    public function canBeKilled()
    {
        return !$this->canBeStarted();
    }

    public function getTitle()
    {
        return $this->mt_name . " (" . $this->mt_command . ")";
    }

    /***
     * @return MaintenanceToolRun
     * @throws \Exception
     */
    public function start($user, $writeLogMessage = true)
    {
        if ($writeLogMessage) {
            Log::createLogMessage("Запущен тул {$this->getTitle()}", $user);
        }

        if (!$this->canBeStarted()) {
            throw new \Exception("Invalid tool status");
        }

        $mtr = new MaintenanceToolRun();

        $mtr->attributes = [
            'mtr_maintenance_tool_obj_id' => $this->obj_id,
            'mtr_runner_user' => $user,
            'mtr_status' => MaintenanceToolRun::STATUS_NEW,
        ];

        if ($mtr->save()) {
            $messageModel = (new \RdsSystem\Factory(\Yii::$app->debugLogger))->getMessagingRdsMsModel($this->mt_environment);
            foreach (Worker::find()->all() as $worker) {
                /** @var $worker Worker */
                $messageModel->sendMaintenanceToolStart(
                    $worker->worker_name,
                    new \RdsSystem\Message\MaintenanceTool\Start($mtr->obj_id, $this->mt_command)
                );
            }
        }

        return $mtr;
    }

    /**
     * @param $user
     *
     * @throws \Exception
     */
    public function stop($user)
    {
        /** @var $mtr MaintenanceToolRun */
        $mtr = MaintenanceToolRun::findByAttributes([
            'mtr_maintenance_tool_obj_id' => $this->obj_id,
            'mtr_status' => [MaintenanceToolRun::STATUS_IN_PROGRESS],
        ]);

        if (!$mtr) {
            throw new \Exception("Can't stop tool, it's not running");
        }

        Log::createLogMessage("Остановлен тул {$this->getTitle()}", $user);

        $messageModel = (new \RdsSystem\Factory(\Yii::$app->debugLogger))->getMessagingRdsMsModel($this->mt_environment);
        foreach (Worker::find()->all() as $worker) {
            /** @var $worker Worker */
            $messageModel->sendUnixSignalToGroup(
                $worker->worker_name,
                new \RdsSystem\Message\UnixSignalToGroup($mtr->mtr_pid)
            );
        }
    }
}
