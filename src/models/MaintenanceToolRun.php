<?php
namespace app\models;

use app\components\ActiveRecord;

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
class MaintenanceToolRun extends ActiveRecord
{
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in-progress';
    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';

    private $progressPercent = false;

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.maintenance_tool_run';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['mtr_maintenance_tool_obj_id', 'mtr_runner_user', 'mtr_status'], 'required'),
            array(['obj_status_did'], 'number', 'integerOnly' => true),
            array(['mtr_runner_user'], 'string', 'max' => 256),
            array(['mtr_log', 'mtr_pid'], 'safe'),
        );
    }

    public function getMtrMaintenanceTool()
    {
        return MaintenanceTool::find()->where(['mtr_maintenance_tool_obj_id' => $this->obj_id])->one();
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

        /** @var $lastSuccessBefore MaintenanceToolRun */
        $lastSuccessBefore = self::find()->where([
            'mtr_maintenance_tool_obj_id' => $this->mtr_maintenance_tool_obj_id,
            'mtr_status' => MaintenanceToolRun::STATUS_DONE,
        ])->orderBy('obj_id desc')->limit(1)->one();
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

            $regex = '~\[([^\]]+)\]\s*' . preg_quote($message) . '\s*~';
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
