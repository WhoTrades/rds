<?php
namespace app\models;

use app\components\ActiveRecord;

/**
 * This is the model class for table "rds.build".
 *
 * The followings are the available columns in table 'rds.build':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property integer $build_release_request_obj_id
 * @property integer $build_worker_obj_id
 * @property integer $build_project_obj_id
 * @property string $build_status
 * @property string $build_attach
 * @property string $build_time_log
 *
 * The followings are the available model relations:
 * @property Worker $worker
 * @property Project $project
 * @property ReleaseRequest $releaseRequest
 */
class Build extends ActiveRecord
{
    const STATUS_NEW = 'new';
    const STATUS_BUILDING = 'building';
    const STATUS_BUILT = 'built';
    const STATUS_INSTALLED = 'installed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_USED = 'used';
    const STATUS_PREPROD_USING = 'preprod_using';
    const STATUS_PREPROD_MIGRATIONS = 'preprod_migrations';

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.build';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['build_worker_obj_id'], 'required'),
            array(['obj_status_did'], 'number'),
            array(['build_status'], 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'build_worker_obj_id', 'build_status', 'build_attach'], 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return Worker
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['obj_id' => 'build_worker_obj_id']);
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['obj_id' => 'build_project_obj_id']);
    }

    public function getReleaseRequest()
    {
        return ReleaseRequest::find()->where(['obj_id' => $this->build_release_request_obj_id])->one();
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
            'build_worker_obj_id' => 'Worker ID',
            'build_status' => 'Status',
            'build_attach' => 'Attach',
        );
    }

    public function getProgressbarInfo()
    {
        $prev = self::find()->where([
            'build_status' => [Build::STATUS_INSTALLED, Build::STATUS_USED],
            'build_project_obj_id' => $this->build_project_obj_id,
            'build_worker_obj_id' => $this->build_worker_obj_id,
        ])->orderBy('obj_id desc')->one();

        $dataCurrent = array_reverse(array_keys(json_decode($this->build_time_log, true)));

        $data = json_decode($prev->build_time_log, true);
        if (!$data) {
            return null;
        }
        $lastPrev = end($data);

        $currentTime = 0;
        $currentKey = '';
        foreach ($dataCurrent as $currentKey) {
            if (isset($data[$currentKey])) {
                $currentTime = $data[$currentKey];
                break;
            }
        }

        $percent = 100*$currentTime/$lastPrev;

        return [$percent, $currentKey];
    }

    public static function getInstallingStatuses()
    {
        return [self::STATUS_BUILDING, self::STATUS_BUILT, self::STATUS_PREPROD_USING, self::STATUS_PREPROD_MIGRATIONS];
    }

    public function determineHumanReadableError()
    {
        $regexes = [
            '~Execution of target "merge-(?:(?:js)|(?:css))" failed for the following reason: Task exited with code 10~' => "Ошибка в MergeJS/CSS, обращайтесь к фронтдендщикам",
            '~HTTP request sent, awaiting response... (5\d{2} [\w -]*)~' => "DEV не отдает словарь: $1. Попробуйте пересобрать",
            '~ssh: connect to host ([\w-]+.whotrades.net) port 22: No route to host~' => "$0. Обратитесь к администратору",
            '~E: Unable to lock the administration directory \(/var/lib/dpkg/\), is another process using it\?~' => "На сервере администратор что-то устанавливает. Пересоберите позже",
            '~ssh: connect to host ([\w-]+.whotrades.net) port 22: Connection timed out~' => "Сервер $1 не отвечает. Обратитесь к администратору<br />$0",
            '~([\w-]+.whotrades.net):.*No space left on device~' => "Закончилось место на <b>$1</b>. Обратитесь к администратору",
            '~ target=sentry, target=raven, event_id=(\w+)~' => "Sentry <a href='https://sentry.whotrades.com/sentry/{$this->project->project_name}/?query=$1'><b>$1</b></a>.",
        ];

        foreach ($regexes as $regex => $text) {
            if (preg_match($regex, $this->build_attach, $ans)) {
                $i = 0;
                return str_replace(array_map(function() use (&$i){
                    return '$' . ($i++);
                }, $ans), $ans, $text);
            }
        }

        return null;
    }
}
