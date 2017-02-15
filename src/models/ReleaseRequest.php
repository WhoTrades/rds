<?php
/**
 * This is the model class for table "rds.release_request".
 *
 * The followings are the available columns in table 'rds.release_request':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $rr_user
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
    const STATUS_CODES               = 'codes';
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
    public function tableName()
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
            array('obj_created, obj_modified, rr_user, rr_comment, rr_project_obj_id, rr_build_version, rr_release_version', 'required'),
            array('obj_status_did, rr_project_obj_id', 'numerical', 'integerOnly' => true),
            array('obj_id, obj_created, obj_modified, obj_status_did, rr_user, rr_comment, rr_project_obj_id, rr_build_version, rr_status', 'safe', 'on' => 'search'),
            array('rr_project_owner_code, rr_release_engineer_code', 'safe', 'on' => 'use'),
            array('rr_project_obj_id', 'checkForReleaseReject'),
            array('rr_project_obj_id', 'checkDeploymentEnabled'),
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

        $rejects = ReleaseReject::model()->findAllByAttributes([
            'rr_project_obj_id' => $this->rr_project_obj_id,
            'rr_release_version' => $this->rr_release_version,
        ]);

        if ($rejects) {
            $messages = '';
            foreach ($rejects as $reject) {
                $messages[] = "$reject->rr_comment ($reject->rr_user)";
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
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'project' => array(self::BELONGS_TO, 'Project', 'rr_project_obj_id'),
            'builds' => array(self::HAS_MANY, 'Build', 'build_release_request_obj_id'),
            'hardMigrations' => array(self::HAS_MANY, 'HardMigration', 'migration_release_request_obj_id'),
            'leader' => array(self::BELONGS_TO, 'ReleaseRequest', 'rr_leading_id'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function defaultScope()
    {
        return [
            'alias'     => 'releaserequest',
            'condition' => 'releaserequest.obj_status_did = ' . \ServiceBase_IHasStatus::STATUS_ACTIVE,
        ];
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
            'rr_user' => 'Пользователь',
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

        $criteria->compare('releaserequest.obj_id', $this->obj_id);
        $criteria->compare('releaserequest.obj_created', $this->obj_created);
        $criteria->compare('releaserequest.obj_modified', $this->obj_modified);
        $criteria->compare('releaserequest.obj_status_did', $this->obj_status_did);
        $criteria->compare('releaserequest.rr_user', $this->rr_user, true);
        $criteria->compare('releaserequest.rr_status', $this->rr_status);
        $criteria->compare('releaserequest.rr_comment', $this->rr_comment, true);
        $criteria->compare('releaserequest.rr_project_obj_id', $this->rr_project_obj_id);
        $criteria->compare('releaserequest.rr_build_version', $this->rr_build_version, true);
        $criteria->order = 'releaserequest.obj_created desc';
        $criteria->with = array('builds', 'builds.worker', 'builds.project', 'hardMigrations');

        return new ReleaseRequestSearchDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * @param bool $real
     * {@inheritdoc}
     */
    public function delete($real = null)
    {
        $this->obj_status_did = \ServiceBase_IHasStatus::STATUS_DELETED;

        return (bool) $real ? parent::delete() : $this->save();
    }

    /**
     * @return int
     */
    public function countNotFinishedBuilds()
    {
        $c = new CDbCriteria();
        $c->compare('build_release_request_obj_id', $this->obj_id);
        $c->compare('build_status', '<>' . Build::STATUS_INSTALLED);

        return Build::model()->count($c);
    }

    /** @return ReleaseRequest|null */
    public function getOldReleaseRequest()
    {
        return self::model()->findByAttributes(array(
            'rr_build_version' => $this->rr_old_version,
            'rr_project_obj_id' => $this->rr_project_obj_id,
        ));
    }

    /** @return ReleaseRequest|null */
    public function getUsedReleaseRequest()
    {
        return self::model()->findByAttributes(array(
            'rr_status' => self::STATUS_USED,
            'rr_project_obj_id' => $this->rr_project_obj_id,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ReleaseRequest the static model class
     */
    public static function model($className = null)
    {
        return parent::model($className ?: __CLASS__);
    }

    /**
     * @return bool
     */
    public function canBeUsed()
    {
        return in_array($this->rr_status, array(self::STATUS_INSTALLED, self::STATUS_OLD));
    }

    /**
     * @return bool
     */
    public function canByUsedImmediately()
    {
        return !empty(Yii::app()->params['useImmediately'])
                || (
                    in_array($this->rr_status, array(self::STATUS_OLD))
                    && (time() - $this->getLastTimeOnProdTimestamp() < self::IMMEDIATELY_TIME)
                );
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
            self::STATUS_CODES,
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
     * @throws Exception
     */
    public function createBuildTasks()
    {
        $this->project->incrementBuildVersion($this->rr_release_version);
        $list = Project2worker::model()->findAllByAttributes(array(
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

        Yii::app()->EmailNotifier->sendRdsReleaseRequestNotification($this->rr_user, $this->project->project_name, $this->rr_comment);
        $text = "{$this->rr_user} requested {$this->project->project_name}. {$this->rr_comment}";
        foreach (explode(",", \Yii::app()->params['notify']['releaseRequest']['phones']) as $phone) {
            if (!$phone) {
                continue;
            }
            Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $text);
        }

        Log::createLogMessage("Создан {$this->getTitle()}");
    }

    /**
     * Отправляет задачи на сборку проекта
     */
    public function sendBuildTasks()
    {
        foreach ($this->builds as $build) {
            $c = new CDbCriteria();
            $c->compare('rr_build_version', '<' . $build->releaseRequest->rr_build_version);
            $c->compare('rr_status', ReleaseRequest::getInstalledStatuses());
            $c->compare('rr_project_obj_id', $build->releaseRequest->rr_project_obj_id);
            $c->order = 'rr_build_version desc';
            $lastSuccess = ReleaseRequest::model()->find($c);

            // an: Отправляем задачу в Rabbit на сборку
            (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel()->sendBuildTask($build->worker->worker_name, new \RdsSystem\Message\BuildTask(
                $build->obj_id,
                $build->project->project_name,
                $build->releaseRequest->rr_build_version,
                $build->releaseRequest->rr_release_version,
                $lastSuccess ? $lastSuccess->project->project_name . '-' . $lastSuccess->rr_build_version : null,
                RdsDbConfig::get()->preprod_online
            ));
        }
    }

    /**
     * Отправляет задачи на переключение на сборку
     * @param string $initiatorUserName
     *
     * @throws Exception
     */
    public function sendUseTasks($initiatorUserName)
    {
        $this->rr_status = \ReleaseRequest::STATUS_USING;
        $this->rr_revert_after_time = date("r", time() + self::USE_ATTEMPT_TIME);
        Log::createLogMessage("USE {$this->getTitle()}");

        foreach ($this->project->project2workers as $p2w) {
            /** @var Project2worker $p2w */
            (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel()->sendUseTask(
                $p2w->worker->worker_name,
                new \RdsSystem\Message\UseTask(
                    $this->project->project_name,
                    $this->obj_id,
                    $this->rr_build_version,
                    $initiatorUserName
                )
            );
        }

        $this->save();
        Yii::app()->webSockets->send('updateAllReleaseRequests', []);
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
     * @throws Exception
     */
    public function parseCronConfig($forcePackage = null)
    {
        $group = null;
        /** @var $debugLogger \ServiceBase_IDebugLogger */
        $debugLogger = Yii::app()->debugLogger;

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
                $debugLogger->message("Can't parse line $line");
                continue;
            }

            if (preg_match('~\s*--sys__package=([\w.-]+)~', $line, $ans)) {
                $package = $forcePackage ?: $ans[1];
            } else {
                $debugLogger->message("Can't parse line $line");
                continue;
            }

            if (!$job = ToolJob::model()->findByAttributes([
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
                $debugLogger->message("Can't save ToolJob: " . json_encode($job->errors, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
