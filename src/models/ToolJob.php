<?php
namespace app\models;

use app\components\ActiveRecord;

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
    public static function tableName()
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
            array(['project_obj_id', 'key', 'command', 'version'], 'required'),
            array(['obj_status_did'], 'number'),
            array(['key'],     'string', 'max' => 12),
            array(['group'],   'string', 'max' => 250),
            array(['package'], 'string', 'max' => 64),
            array(['command'], 'string', 'max' => 1000),
            array(['version'], 'string', 'max' => 16),
        );
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['obj_id' => 'project_obj_id']);
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
     * @return ToolJobStopped|null
     */
    public function getToolJobStopped()
    {
        /** @var $stopped ToolJobStopped */
        $stopped = ToolJobStopped::findByAttributes([
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
     * @return string
     * @throws \Exception
     */
    public function getLoggerTag()
    {
        if (!preg_match('~local2.info -t (\S*)~', $this->command, $ans)) {
            throw new \Exception("Can't find logger tag at command $this->command");
        }

        return $ans[1];
    }

    /**
     * @param bool $directImageUrl
     * @param int  $width
     * @param int  $height
     * @param bool $graphOnly
     *
     * @return string
     */
    public function getSmallCpuUsageGraphSrc($directImageUrl = true, $width = 100, $height = 60, $graphOnly = true)
    {
        return $this->getGraphiteCpuStatsUrl('timeCpu', $directImageUrl, $width, $height, $graphOnly);
    }

    /**
     * @param bool $directImageUrl
     * @param int  $width
     * @param int  $height
     * @param bool $graphOnly
     *
     * @return string
     */
    public function getSmallTimeRealGraphSrc($directImageUrl = true, $width = 100, $height = 60, $graphOnly = true)
    {
        return $this->getGraphiteCpuStatsUrl('timeReal', $directImageUrl, $width, $height, $graphOnly);
    }

    private function getGraphiteCpuStatsUrl($type, $directImageUrl = true, $width = 100, $height = 60, $graphOnly = true)
    {
        $baseUrl = \Yii::$app->graphite->GUIUrl;
        $env = \Yii::$app->graphite->env;
        $direct = $directImageUrl ? "render/" : "";
        $loggerTag = strtoupper($this->getLoggerTag()) . "-" . strtoupper($this->key);
        $params = [
            'width'     => $width,
            'height'    => $height,
            'from'      => '-24hours',
            'target'    => "sumSeries(stats.gauges.rds.$env.system." . strtoupper($this->getProjectName()) . ".tool." . $loggerTag . ".$type)",
            'graphOnly' => json_encode($graphOnly),
            'yMin'      => 0,
        ];

        return "{$baseUrl}{$direct}?" . http_build_query($params);
    }

    /**
     * @return string
     */
    public function getProjectName()
    {
        return preg_replace('~-[\d.]+$~', '', $this->package);
    }
}
