<?php
namespace app\models;

use app\components\Status;
use RdsSystem\Message\BuildTask;
use Yii;
use app\models\User\User;
use yii\data\Sort;
use app\components\ActiveRecord;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use app\modules\Wtflow\models\HardMigration;
use app\modules\Whotrades\models\ToolJob;

/**
 * This is the model class for table "rds.release_request".
 *
 * The followings are the available columns in table 'rds.release_request':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property int $rr_user_id
 * @property string $rr_comment
 * @property string $rr_project_obj_id
 * @property integer $rr_leading_id
 * @property string $rr_status
 * @property string $rr_old_version
 * @property string $rr_build_version
 * @property string $rr_project_owner_code
 * @property string $rr_release_engineer_code
 * @property string $rr_project_owner_code_entered
 * @property string $rr_release_engineer_code_entered
 * @property string $rr_last_time_on_prod
 * @property string $rr_revert_after_time
 * @property string $rr_release_version
 * @property string $rr_new_migration_count
 * @property string $rr_new_migrations
 * @property string $rr_migration_status
 * @property string $rr_migration_error
 * @property string $rr_new_post_migrations
 * @property string $rr_post_migration_status
 * @property string $rr_built_time
 * @property string $rr_cron_config
 * @property string $rr_build_started
 *
 * @property Build[] $builds
 * @property User $user
 * @property Project $project
 * @property HardMigration[] $hardMigrations
 * @property ReleaseRequest $leader
 */
class ReleaseRequest extends ActiveRecord
{
    const USE_ATTEMPT_TIME = 40;
    const IMMEDIATELY_TIME = 900;

    const STATUS_NEW                 = 'new';
    const STATUS_FAILED              = 'failed';
    const STATUS_INSTALLED           = 'installed';
    const STATUS_USING               = 'using';
    const STATUS_USED                = 'used';
    const STATUS_OLD                 = 'old';
    const STATUS_CANCELLING          = 'cancelling';
    const STATUS_CANCELLED           = 'cancelled';

    const MIGRATION_STATUS_NONE      = 'none';
    const MIGRATION_STATUS_UPDATING  = 'updating';
    const MIGRATION_STATUS_FAILED    = 'failed';
    const MIGRATION_STATUS_UP        = 'up';

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.release_request';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('rr_status', 'default', 'value' => self::STATUS_NEW),
            array(['rr_user_id', 'rr_status', 'rr_comment', 'rr_project_obj_id', 'rr_build_version', 'rr_release_version'], 'required'),
            array(['obj_status_did', 'rr_project_obj_id'], 'number'),
            array(
                ['obj_id', 'obj_created', 'obj_status_did', 'rr_user_id', 'rr_comment', 'rr_project_obj_id', 'rr_build_version', 'rr_status'],
                'safe',
                'on' => 'search',
            ),
            array(['rr_project_owner_code', 'rr_release_engineer_code'], 'safe', 'on' => 'use'),
            array(['rr_release_version'], 'checkForReleaseReject'),
            array(['rr_project_obj_id'], 'checkDeploymentEnabled'),
        );
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function checkForReleaseReject($attribute, $params)
    {
        // an: Правило действует только для новых запросов на релиз
        if (!$this->isNewRecord || !$this->rr_project_obj_id) {
            return;
        }

        $rejects = ReleaseReject::findAllByAttributes([
            'rr_project_obj_id' => $this->rr_project_obj_id,
            'rr_release_version' => $this->rr_release_version,
        ]);

        if ($rejects) {
            $messages = '';
            foreach ($rejects as $reject) {
                /** @var $reject ReleaseReject */
                $messages[] = "$reject->rr_comment ({$reject->user->email})";
            }
            $this->addError($attribute, 'Запрет на релиз: ' . implode("; ", $messages));
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function checkDeploymentEnabled($attribute, $params)
    {
        $deployment_enabled = RdsDbConfig::get()->deployment_enabled;
        if (!$deployment_enabled) {
            $this->addError($attribute, 'Деплой временно запрещен. Обратитесь к администратору, причина: ' . RdsDbConfig::get()->deployment_enabled_reason);
        }
    }

    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return parent::find()->andWhere([
            'obj_status_did' => Status::ACTIVE,
        ]);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'Номер',
            'obj_created' => 'Дата создания',
            'obj_modified' => 'Дата модификаии',
            'obj_status_did' => 'Системный статус',
            'rr_user_id' => 'Пользователь',
            'rr_status' => 'Статус',
            'rr_comment' => 'Комментарий',
            'rr_project_obj_id' => 'Проект',
            'rr_build_version' => 'Версия',
            'rr_project_owner_code' => 'SMS Код',
            'rr_release_engineer_code' => 'Код 2',
            'rr_release_version' => 'Основная версия',
        );
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = self::find()->andWhere(array_filter([
            'rr_status' => $params['rr_status'],
            'rr_project_obj_id' => $params['rr_project_obj_id'],
        ]));
        $query->with = ['user', 'user.profile', 'project', 'builds', 'builds.project', 'hardMigrations'];
        $query->andFilterWhere(['like', 'rr_comment', $params['rr_comment']]);
        $query->andFilterWhere(['like', 'rr_build_version', $params['rr_build_version']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => new Sort(['defaultOrder' => ['obj_id' => SORT_DESC]]),
        ]);
        $this->load($params, 'search');

        return $dataProvider;
    }

    /**
     * @param bool $real
     * {@inheritdoc}
     */
    public function delete($real = null)
    {
        $this->obj_status_did = Status::DELETED;

        return (bool) $real ? parent::delete() : $this->save();
    }

    /**
     * @return int
     */
    public function countNotFinishedBuilds()
    {
        return Build::find()->where(['build_release_request_obj_id' => $this->obj_id])->andWhere(['<>', 'build_status', Build::STATUS_INSTALLED])->count();
    }

    /** @return ReleaseRequest|null */
    public function getOldReleaseRequest()
    {
        return self::find()->where(array(
            'rr_build_version' => $this->rr_old_version,
            'rr_project_obj_id' => $this->rr_project_obj_id,
        ))->one();
    }

    /** @return ReleaseRequest|null */
    public function getUsedReleaseRequest()
    {
        return self::findByAttributes(array(
            'rr_status' => self::STATUS_USED,
            'rr_project_obj_id' => $this->rr_project_obj_id,
        ));
    }

    /**
     * @return bool
     */
    public function canBeUsed()
    {
        return in_array($this->rr_status, array(self::STATUS_INSTALLED, self::STATUS_OLD));
    }

    /**
     * @return int
     */
    public function getLastTimeOnProdTimestamp()
    {
        if ($this->rr_status == self::STATUS_USED) {
            return time();
        }

        return $this->rr_last_time_on_prod ? strtotime($this->rr_last_time_on_prod) : 0;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "Запрос релиза #$this->obj_id {$this->project->project_name}::{$this->rr_build_version} ($this->rr_comment)";
    }

    /**
     * @return bool
     */
    public function isInstalledStatus()
    {
        return in_array($this->rr_status, self::getInstalledStatuses());
    }

    /**
     * @return bool
     */
    public function isUsedStatus()
    {
        return in_array($this->rr_status, self::getUsedStatuses());
    }

    /**
     * @return array
     */
    public static function getInstalledStatuses()
    {
        return [
            self::STATUS_INSTALLED,
            self::STATUS_OLD,
            self::STATUS_USED,
        ];
    }

    /**
     * @return array
     */
    public static function getUsedStatuses()
    {
        return [self::STATUS_USED];
    }

    /**
     * @return string
     */
    public function getBuildTag()
    {
        return $this->project->project_name . "-" . $this->rr_build_version;
    }

    /**
     * @throws \Exception
     */
    public function createBuildTasks()
    {
        $this->project->incrementBuildVersion($this->rr_release_version);
        $list = Project2worker::findAllByAttributes(array(
            'project_obj_id' => $this->rr_project_obj_id,
        ));

        $tasks = [];
        foreach ($list as $val) {
            /** @var $val Project2worker */
            $task = new Build();
            $task->build_release_request_obj_id = $this->obj_id;
            $task->build_worker_obj_id = $val->worker_obj_id;
            $task->build_project_obj_id = $val->project_obj_id;
            $task->save();

            $tasks[] = $task;
        }

        \Yii::$app->EmailNotifier->sendRdsReleaseRequestNotification($this->user->email, $this->project->project_name, $this->rr_comment);
        $text = "{$this->user->email} requested {$this->project->project_name}. {$this->rr_comment}";
        foreach (explode(",", \Yii::$app->params['notify']['releaseRequest']['phones']) as $phone) {
            if (!$phone) {
                continue;
            }
            \Yii::$app->smsSender->sendSms($phone, $text);
        }

        Log::createLogMessage("Создан {$this->getTitle()}");
    }

    /**
     * Отправляет задачи на сборку проекта
     */
    public function sendBuildTasks()
    {
        foreach ($this->builds as $build) {
            /** @var $lastSuccess ReleaseRequest | null */
            $lastSuccess = static::find()->where([
                'rr_status' => ReleaseRequest::getInstalledStatuses(),
                'rr_project_obj_id' => $build->releaseRequest->rr_project_obj_id,
            ])->andWhere(['<', 'rr_build_version', $build->releaseRequest->rr_build_version])->
            orderBy('rr_build_version desc')->one();

            // an: Отправляем задачу в Rabbit на сборку
            (new \RdsSystem\Factory())->getMessagingRdsMsModel()->sendBuildTask(
                $build->worker->worker_name,
                new BuildTask(
                    $build->obj_id,
                    $build->project->project_name,
                    $build->releaseRequest->rr_build_version,
                    $build->releaseRequest->rr_release_version,
                    $lastSuccess ? $lastSuccess->getBuildTag() : null,
                    $build->releaseRequest->project->script_migration_new,
                    $build->releaseRequest->project->script_deploy,
                    $build->releaseRequest->project->script_cron,
                    $build->project->getProjectServersArray()
                )
            );
        }
    }

    /**
     * Отправляет задачи на переключение на сборку
     * @param string $initiatorUserName
     *
     * @throws \Exception
     */
    public function sendUseTasks($initiatorUserName)
    {
        $this->rr_status = ReleaseRequest::STATUS_USING;
        $this->rr_revert_after_time = date("r", time() + self::USE_ATTEMPT_TIME);
        $this->rr_old_version = $this->project->project_current_version;
        Log::createLogMessage("USE {$this->getTitle()}");

        foreach ($this->project->project2workers as $p2w) {
            /** @var Project2worker $p2w */
            (new \RdsSystem\Factory())->getMessagingRdsMsModel()->sendUseTask(
                $p2w->worker->worker_name,
                new \RdsSystem\Message\UseTask(
                    $this->project->project_name,
                    $this->obj_id,
                    $this->rr_build_version,
                    $initiatorUserName,
                    $this->project->getProjectServersArray()
                )
            );
        }

        $this->save();
    }

    /**
     * @return string
     */
    public function getCronConfigCleaned()
    {
        $text = $this->rr_cron_config;
        $text = preg_replace('~ --sys__key=\w+~', '', $text);
        $text = preg_replace('~ --sys__package=[\w-]+-[\d.]+~', '', $text);

        return $text;
    }

    /**
     * @param string $forcePackage
     *
     * @throws \Exception
     */
    public function parseCronConfig($forcePackage = null)
    {
        $group = null;

        foreach (array_filter(explode("\n", str_replace("\r", "", $this->rr_cron_config))) as $line) {
            if (preg_match('~^#\s*(\S.*)$~', $line, $ans)) {
                $group = $ans[1];
                continue;
            }
            if (preg_match('~^\w+\=.*~', $line)) {
                continue;
            }

            if (preg_match('~\s*--sys__key=(\w+)~', $line, $ans)) {
                $key = $ans[1];
            } else {
                Yii::info("Can't parse line $line");
                continue;
            }

            if (preg_match('~\s*--sys__package=([\w.-]+)~', $line, $ans)) {
                $package = $forcePackage ?: $ans[1];
            } else {
                Yii::info("Can't parse line $line");
                continue;
            }

            if (!$job = ToolJob::findByAttributes([
                'key' => $key,
                'package' => $package,
                'project_obj_id' => $this->rr_project_obj_id,
            ])) {
                $job = new ToolJob();
                $job->key = $key;
                $job->package = $package;
                $job->project_obj_id = $this->rr_project_obj_id;
            }

            $job->version = $this->rr_build_version;
            $job->group = $group;
            $job->command = $line;

            if (!$job->save()) {
                Yii::info("Can't save ToolJob: " . json_encode($job->errors, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * @return ActiveQuery
     */
    public function getBuilds()
    {
        return $this->hasMany(Build::className(), ['build_release_request_obj_id' => 'obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getHardMigrations()
    {
        return $this->hasMany(HardMigration::className(), ['migration_release_request_obj_id' => 'obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'rr_user_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['obj_id' => 'rr_project_obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getReleaseRequests()
    {
        return $this->hasMany(releaseRequest::className(), ['rr_leading_id' => 'obj_id']);
    }
}
