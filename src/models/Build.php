<?php

namespace whotrades\rds\models;

use whotrades\rds\components\ActiveRecord;
use whotrades\rds\models\User\User;
use yii\db\ActiveQuery;

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
 * @property User $user
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
     * @return ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::class, ['obj_id' => 'build_worker_obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['obj_id' => 'build_project_obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getReleaseRequest()
    {
        return $this->hasOne(ReleaseRequest::class, ['obj_id' => 'build_release_request_obj_id']);
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

    /**
     * На основании предыдущей успешной сборки высчитывает текущий процент сборки
     * @return array - [percent, lastTaskKey]
     */
    public function getProgressBarInfo()
    {
        /** @var Build $prev */
        $prev = self::find()->where([
            'build_status' => [Build::STATUS_INSTALLED, Build::STATUS_USED],
            'build_project_obj_id' => $this->build_project_obj_id,
            'build_worker_obj_id' => $this->build_worker_obj_id,
        ])->orderBy('obj_id desc')->limit(1)->one();

        if (!$prev) {
            return null;
        }

        $currentActions = array_reverse(array_keys(json_decode($this->build_time_log, true)));

        $timeLogPrev = json_decode($prev->build_time_log, true);
        if (!$timeLogPrev) {
            return null;
        }
        $buildTimePrev = strtotime($prev->releaseRequest->rr_built_time) - reset($timeLogPrev);
        if (!$buildTimePrev) {
            return null;
        }

        $currentTime = 0;
        $action = '';
        foreach ($currentActions as $action) {
            if (isset($timeLogPrev[$action])) {
                // ag: Backward compatibility with old build_time_log #WTA-1754
                if (reset($timeLogPrev) < strtotime($prev->releaseRequest->obj_created)) {
                    $currentTime = $timeLogPrev[$action];
                } else {
                    $currentTime = $timeLogPrev[$action] - reset($timeLogPrev);
                }
                break;
            }
        }

        $percent = 100 * $currentTime / $buildTimePrev;

        return [$percent, $action];
    }

    /**
     * Список всех статусов, при которых проект считается успешно собранным
     * @return array
     */
    public static function getInstallingStatuses()
    {
        return [self::STATUS_BUILDING, self::STATUS_BUILT, self::STATUS_PREPROD_USING, self::STATUS_PREPROD_MIGRATIONS];
    }

    /**
     * @return mixed|null
     */
    public function determineHumanReadableError()
    {
        $sentryParams = \Yii::$app->params['sentry'];
        $sentryProjectName = $sentryParams['projectNameMap'][$this->project->project_name] ?? $this->project->project_name;

        $regexes = [
            '~Execution of target "merge-(?:(?:js)|(?:css))" failed for the following reason: Task exited with code 10~' => "Ошибка в MergeJS/CSS, обращайтесь к фронтдендщикам",
            '~HTTP request sent, awaiting response... (5\d{2} [\w -]*)~' => "DEV не отдает словарь: $1. Попробуйте пересобрать",
            '~ssh: connect to host ([\w-]+.whotrades.net) port 22: No route to host~' => "$0. Обратитесь к администратору",
            '~E: Unable to lock the administration directory \(/var/lib/dpkg/\), is another process using it\?~'
            => "На сервере администратор что-то устанавливает. Пересоберите позже",
            '~ssh: connect to host ([\w-]+.whotrades.net) port 22: Connection timed out~' => "Сервер $1 не отвечает. Обратитесь к администратору<br />$0",
            '~([\w-]+.whotrades.net):.*No space left on device~' => "Закончилось место на <b>$1</b>. Обратитесь к администратору",
            '~ target=sentry, target=raven, event_id=(\w+)~' => "Sentry <a href='{$sentryParams['baseUrl']}{$sentryProjectName}/?query=$1'><b>$1</b></a>.",
        ];

        foreach ($regexes as $regex => $text) {
            if (preg_match($regex, $this->build_attach, $ans)) {
                $i = 0;

                return str_replace(array_map(function () use (&$i) {
                    return '$' . ($i++);
                }, $ans), $ans, $text);
            }
        }

        return null;
    }
}
