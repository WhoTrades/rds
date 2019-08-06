<?php
namespace whotrades\rds\models;

use whotrades\rds\components\Status;
use whotrades\RdsSystem\Message\BuildTask;
use whotrades\RdsSystem\Message\InstallTask;
use whotrades\RdsSystem\Message\UseTask;
use whotrades\rds\models\User\User;
use yii\data\Sort;
use whotrades\rds\components\ActiveRecord;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

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
 * @property string $rr_use_text
 * @property string $rr_last_error_text
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
 * @property ReleaseRequest $leader
 */
class ReleaseRequest extends ActiveRecord
{
    const USE_ATTEMPT_TIME = 40;
    const IMMEDIATELY_TIME = 900;

    const STATUS_NEW                 = 'new';
    const STATUS_FAILED              = 'failed';
    const STATUS_BUILDING            = 'building';
    const STATUS_BUILT               = 'built';
    const STATUS_INSTALLING          = 'installing';
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

    const BUILD_LOG_BUILD_ERROR      = 'build error';
    const BUILD_LOG_BUILD_SUCCESS    = 'build success';

    const BUILD_LOG_INSTALL_START    = 'install start';
    const BUILD_LOG_INSTALL_ERROR    = 'install error';
    const BUILD_LOG_INSTALL_SUCCESS  = 'install success';

    const BUILD_LOG_USING_START      = 'using start';
    const BUILD_LOG_USING_ERROR      = 'using error';
    const BUILD_LOG_USING_SUCCESS    = 'using success';

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
     * @param int $projectObjId
     * @param int $userId
     * @param string $comment
     *
     * @return self[]
     *
     * @throws \Exception
     */
    public static function create($projectObjId, $releaseVersion, $userId, $comment)
    {
        $model = new self();

        $model->rr_project_obj_id = $projectObjId;
        $model->rr_release_version = $releaseVersion;
        $model->rr_comment = $comment;
        $model->rr_user_id = $userId;
        if ($model->rr_project_obj_id) {
            $model->rr_build_version = $model->project->getNextVersion($model->rr_release_version);
        }
        if ($model->save()) {
            $childModels = [];
            foreach ($model->project->project2ProjectList as $project2ProjectObject) {
                /** @var Project $childProject */
                $childProject = $project2ProjectObject->child;

                $childReleaseRequest = new self();
                $childReleaseRequest->rr_user_id = $model->rr_user_id;
                $childReleaseRequest->rr_project_obj_id = $childProject->obj_id;
                $childReleaseRequest->rr_comment =
                    $model->rr_comment . " [child of " . $model->project->project_name . "-$model->rr_build_version]";
                $childReleaseRequest->rr_release_version = $model->rr_release_version;
                $childReleaseRequest->rr_build_version = $childProject->getNextVersion($childReleaseRequest->rr_release_version);
                $childReleaseRequest->rr_leading_id = $model->obj_id;
                $childReleaseRequest->save();

                $childReleaseRequest->createBuildTasks();

                $childModels[] = $childReleaseRequest;
            }

            $model->rr_comment = "$model->rr_comment";
            $model->save();

            $model->createBuildTasks();

            return array_merge([$model], $childModels);
        }

        return [];
    }

    /**
     * @param string $attribute
     */
    public function checkForReleaseReject($attribute)
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
            $messages = [];
            foreach ($rejects as $reject) {
                /** @var $reject ReleaseReject */
                $messages[] = "$reject->rr_comment ({$reject->user->email})";
            }
            $this->addError($attribute, 'Запрет на релиз: ' . implode("; ", $messages));
        }
    }

    /**
     * @param string $attribute
     */
    public function checkDeploymentEnabled($attribute)
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
     * @param int $id
     *
     * @return ReleaseRequest
     */
    public static function findByPkAllStatuses($id)
    {
        return parent::find()->andWhere(['obj_id' => $id])->one();
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
        $query = self::find()->where(array_filter([
            'rr_status' => $params['rr_status'] ?? null,
            'rr_project_obj_id' => $params['rr_project_obj_id'] ?? null,
        ]));
        $query->with = ['user', 'user.profile', 'project', 'builds', 'builds.project'];
        $query->andFilterWhere(['like', 'rr_comment', $params['rr_comment'] ?? ""]);
        $query->andFilterWhere(['like', 'rr_build_version', $params['rr_build_version'] ?? ""]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => new Sort(['defaultOrder' => ['obj_id' => SORT_DESC]]),
        ]);
        $this->load($params, 'search');

        return $dataProvider;
    }

    /**
     * Djpdhfoftn true, если запись в статусе "удалена"
     * @return bool
     */
    public function isDeleted()
    {
        return $this->obj_status_did == Status::DELETED || $this->obj_status_did == Status::DESTROYED;
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
     * @return bool
     */
    public function markAsDestroyed()
    {
        $this->obj_status_did = Status::DESTROYED;

        return (bool) $this->save();
    }

    /**
     * @return int
     */
    public function countNotBuiltBuilds()
    {
        return Build::find()->where(['build_release_request_obj_id' => $this->obj_id])->andWhere(['<>', 'build_status', Build::STATUS_BUILT])->count();
    }

    /**
     * @return int
     */
    public function countNotInstalledBuilds()
    {
        return Build::find()->where(['build_release_request_obj_id' => $this->obj_id])->andWhere(['<>', 'build_status', Build::STATUS_INSTALLED])->count();
    }

    /** @return ReleaseRequest|null */
    public function getOldReleaseRequest()
    {
        return self::findByAttributes(array(
            'rr_build_version' => $this->rr_old_version,
            'rr_project_obj_id' => $this->rr_project_obj_id,
        ));
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
    public function isChild()
    {
        return (bool) $this->rr_leading_id;
    }

    /**
     * @return bool
     */
    public function showCronDiff()
    {
        return in_array($this->rr_status, [self::STATUS_INSTALLED, self::STATUS_BUILT]);
    }

    /**
     * @return bool
     */
    public function showActivationErrors()
    {
        return (bool) $this->rr_last_error_text && $this->rr_status === self::STATUS_INSTALLED;
    }

    /**
     * @return bool
     */
    public function showInstallationErrors()
    {
        return $this->shouldBeInstalled();
    }

    /**
     * Give ability to deploy manually if automated deploy is failed and there are errors
     *
     * @return bool
     */
    public function shouldBeInstalled()
    {
        return $this->rr_status === self::STATUS_BUILT && $this->rr_last_error_text;
    }

    /**
     * @return bool
     */
    public function shouldBeMigrated()
    {
        return (($this->rr_status === self::STATUS_INSTALLED) && $this->rr_new_migration_count);
    }

    /**
     * @return bool
     */
    public function canBeUsed()
    {
        if ($this->isDeleted() ||
            !in_array($this->rr_status, array(self::STATUS_INSTALLED, self::STATUS_OLD)) ||
            $this->shouldBeMigrated() ||
            !$this->canBeUsedChildren()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function canBeUsedChildren()
    {
        /** @var ReleaseRequest $childReleaseRequest */
        foreach ($this->getReleaseRequests()->all() as $childReleaseRequest) {
            if (!$childReleaseRequest->canBeUsed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function canBeReverted()
    {
        if ($this->rr_status == ReleaseRequest::STATUS_USED && ($oldReleaseRequest = $this->getOldReleaseRequest()) && $oldReleaseRequest->canBeUsed()) {
            return true;
        }

        return false;
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
            // an: Отправляем задачу в Rabbit на сборку
            (new \whotrades\RdsSystem\Factory())->getMessagingRdsMsModel()->sendBuildTask(
                $build->worker->worker_name,
                new BuildTask(
                    $build->obj_id,
                    $build->project->project_name,
                    $build->releaseRequest->rr_build_version,
                    $build->releaseRequest->rr_release_version,
                    $build->releaseRequest->project->script_migration_new,
                    $build->releaseRequest->project->script_build,
                    $build->releaseRequest->project->script_cron,
                    $build->project->getProjectServersArray()
                )
            );
        }
    }

    /**
     */
    public function sendInstallTask()
    {
        foreach ($this->builds as $build) {
            // an: Отправляем задачу в Rabbit на раскладку кода по серверам
            (new \whotrades\RdsSystem\Factory())->getMessagingRdsMsModel()->sendInstallTask(
                $build->worker->worker_name,
                new InstallTask(
                    $build->obj_id,
                    $build->project->project_name,
                    $build->releaseRequest->rr_build_version,
                    $build->releaseRequest->rr_release_version,
                    $build->releaseRequest->project->script_deploy,
                    $build->project->getProjectServersArray()
                )
            );
        }
    }

    /**
     * Отправляет задачи на переключение на сборку
     * @param string $initiatorUserName
     * @param bool $withChildren
     *
     * @throws \Exception
     */
    public function sendUseTasks($initiatorUserName, $withChildren = null)
    {
        $withChildren = $withChildren ?? true;

        $this->rr_status = ReleaseRequest::STATUS_USING;
        $this->rr_revert_after_time = date("r", time() + self::USE_ATTEMPT_TIME);
        $this->rr_old_version = $this->project->project_current_version;
        Log::createLogMessage("USE {$this->getTitle()}");

        foreach ($this->project->project2workers as $p2w) {
            /** @var Project2worker $p2w */
            (new \whotrades\RdsSystem\Factory())->getMessagingRdsMsModel()->sendUseTask(
                $p2w->worker->worker_name,
                new UseTask(
                    $this->project->project_name,
                    $this->obj_id,
                    $this->rr_build_version,
                    $initiatorUserName,
                    $this->project->script_use,
                    $this->project->getProjectServersArray()
                )
            );
        }

        $this->save();

        $this->addBuildTimeLog(self::BUILD_LOG_USING_START);

        if ($withChildren) {
            /** @var ReleaseRequest $childReleaseRequest */
            foreach ($this->getReleaseRequests()->all() as $childReleaseRequest) {
                $childReleaseRequest->sendUseTasks($initiatorUserName);
            }
        }
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
     * @return ActiveQuery | Build[]
     */
    public function getBuilds()
    {
        return $this->hasMany(Build::class, ['build_release_request_obj_id' => 'obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'rr_user_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['obj_id' => 'rr_project_obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getReleaseRequests()
    {
        return $this->hasMany(releaseRequest::class, ['rr_leading_id' => 'obj_id']);
    }

    /**
     * @param string $action
     * @param float | null $time
     *
     * @throws \Exception
     */
    public function addBuildTimeLog($action, $time = null)
    {
        $time = $time ?? microtime(true);

        foreach ($this->builds as $build) {
            $data = json_decode($build->build_time_log, true);

            // ag: Starting new action remove results of previous same actions
            switch ($action) {
                case self::BUILD_LOG_INSTALL_START:
                    unset($data[self::BUILD_LOG_INSTALL_ERROR]);
                    unset($data[self::BUILD_LOG_INSTALL_SUCCESS]);

                    break;
                case self::BUILD_LOG_USING_START:
                    unset($data[self::BUILD_LOG_USING_ERROR]);
                    unset($data[self::BUILD_LOG_USING_SUCCESS]);

                    break;
            }

            $data[$action] = (float) $time;
            asort($data);

            $build->build_time_log = json_encode($data);
            $build->save();
        }
    }
}
