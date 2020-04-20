<?php
/**
 * Default migration model class. It supports PRE and POST migrations
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models;

use Yii;
use yii\data\ActiveDataProvider;
use whotrades\rds\models\Migration\Exception\UndefinedClassForState;
use DateTime;

/**
 * This is the model class for table "rds.migration".
 *
 * The followings are the available columns in table 'rds.migration':
 *
 * {@inheritDoc}
 */
class Migration extends MigrationBase
{
    const POST_MIGRATION_STABILIZE_DELAY = '1 week';

    const STATUS_APPLIED = 1;
    const STATUS_STARTED_ROLLBACK = 3;
    const STATUS_STARTED_APPLICATION = 4;
    const STATUS_PENDING = 5;
    const STATUS_FAILED_APPLICATION  = 6;
    const STATUS_FAILED_ROLLBACK  = 7;

    /**
     * @var Migration\StateBase
     */
    private $stateObject;

    private $statusIdToStateClassMap = [
        self::STATUS_APPLIED => Migration\StateApplied::class,
        self::STATUS_STARTED_ROLLBACK => Migration\StateStartedRollBack::class,
        self::STATUS_STARTED_APPLICATION => Migration\StateStartedApplication::class,
        self::STATUS_PENDING => Migration\StatePending::class,
        self::STATUS_FAILED_APPLICATION  => Migration\StateFailedApplication::class,
        self::STATUS_FAILED_ROLLBACK  => Migration\StateFailedRollBack::class,
    ];

    /**
     * @param string $typeName
     *
     * @return int | string
     */
    public static function getTypeIdByName($typeName)
    {
        return array_flip(self::getTypeIdToNameMap())[$typeName] ?? "Unknown type name: {$typeName}";
    }

    /**
     * @return string[]
     */
    public static function getStatusIdToNameMap()
    {
        return [
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_STARTED_ROLLBACK => 'Started Roll Back',
            self::STATUS_STARTED_APPLICATION => 'Started Application',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_FAILED_APPLICATION  => 'Failed Application',
            self::STATUS_FAILED_ROLLBACK  => 'Failed RollBack',
        ];
    }

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.migration';
    }

    /**
     * {@inheritDoc}
     */
    public static function upsert($migrationName, $typeName, Project $project, ReleaseRequest $releaseRequest)
    {
        if (!preg_match('/^[\w\/\\\_\-]+$/', $migrationName)) {
            throw new \Exception("Skip processing {$typeName} migration {$migrationName} of project {$project->project_name}. Malformed name.");

        }

        $migrationTypeId = Migration::getTypeIdByName($typeName);
        $migration = Migration::findByAttributes(
            [
                'migration_type' => $migrationTypeId,
                'migration_name' => $migrationName,
                'migration_project_obj_id' => $project->obj_id,
            ]
        );

        if ($migration) {
            Yii::info("Skip creating {$typeName} migration {$migrationName} of project {$project->project_name}. Already exists in DB");
        } else {
            $migration = new Migration();
            $migration->loadDefaultValues();
            $migration->migration_type = $migrationTypeId;
            $migration->migration_name = $migrationName;
            $migration->migration_project_obj_id = $project->obj_id;
            $migration->migration_release_request_obj_id = $releaseRequest->obj_id;

            $migration->save();
        }

        return $migration;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(
                ['obj_status_did', 'migration_type', 'migration_name', 'migration_project_obj_id'],
                'safe',
                'on' => 'search',
            ),
        );
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $dataProvider = new ActiveDataProvider(
            [
                'query' => self::find()
                    ->where(
                        array_filter(
                            [
                                'obj_status_did' => $params['obj_status_did'] ?? null,
                                'migration_type' => $params['migration_type'] ?? null,
                                'migration_project_obj_id' => $params['migration_project_obj_id'] ?? null,
                            ]
                        )
                    )
                    ->andFilterWhere(['like', 'migration_name', $params['migration_name'] ?? ''])
                    ->orderBy(['obj_created' => SORT_DESC, 'migration_name' => SORT_DESC]),
                'pagination' => ['pageSize' => 15],
            ]
        );
        $this->load($params, 'search');

        return $dataProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected static function getMigrationReadyBeAutoAppliedList()
    {
        return self::getPreMigrationCanBeAppliedList();
    }

    /**
     * @return static[]
     */
    public static function getPreMigrationCanBeAppliedList()
    {
        $preMigrationPendingList = self::find()
            ->andWhere(['migration_type' => self::TYPE_ID_PRE])
            ->andWhere(['IN', 'obj_status_did', [self::STATUS_PENDING, self::STATUS_FAILED_APPLICATION]])
            ->all();

        return array_filter($preMigrationPendingList, function (self $migration) {
            return $migration->canBeApplied();
        });
    }

    /**
     * @return static[]
     */
    public static function getPostMigrationCanBeAppliedList()
    {
        $postMigrationPendingList = self::find()
            ->andWhere(['migration_type' => self::TYPE_ID_POST])
            ->andWhere(['IN', 'obj_status_did', [self::STATUS_PENDING, self::STATUS_FAILED_APPLICATION]])
            ->all();

        return array_filter($postMigrationPendingList, function (self $migration) {
            return $migration->canBeApplied();
        });
    }

    /**
     * @return int
     */
    public static function getPostMigrationCanBeAppliedCount()
    {
        return count(self::getPostMigrationCanBeAppliedList());
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusName()
    {
        return self::getStatusIdToNameMap()[$this->obj_status_did] ?? "Unknown status: {$this->obj_status_did}";
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeName()
    {
        return self::getTypeIdToNameMap()[$this->migration_type] ?? "Unknown type: {$this->migration_type}";
    }

    /**
     * @param int $status
     *
     * @throws \Exception
     */
    public function tryUpdateStatus($status)
    {
        $this->getStateObject()->tryUpdateStatus($status);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus($status)
    {
        $this->obj_status_did = $status;
        $this->save();
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return in_array($this->obj_status_did, [self::STATUS_FAILED_APPLICATION, self::STATUS_FAILED_ROLLBACK]);
    }

    /**
     * @return int
     *
     * @throws \Exception
     */
    public function getWaitingDays()
    {
        if ($this->migration_type !== self::TYPE_ID_POST) {
            return 0;
        }

        $postMigrationAllowTimestamp = strtotime("-" . self::POST_MIGRATION_STABILIZE_DELAY);
        $waitingTime = (new DateTime($this->obj_created))->getTimestamp() - $postMigrationAllowTimestamp;

        if ($waitingTime <= 0) {
            return 0;
        }

        return ceil($waitingTime / (24 * 60 * 60));
    }

    /**
     * {@inheritDoc}
     */
    public function canBeApplied()
    {
        return $this->getStateObject()->canBeApplied();
    }

    /**
     * @return bool
     */
    public function canBeRolledBack()
    {
        return $this->getStateObject()->canBeRolledBack();
    }

    /**
     * {@inheritDoc}
     */
    public function apply()
    {
        $this->getStateObject()->apply();
    }

    /**
     * @return void
     */
    public function rollBack()
    {
        $this->getStateObject()->rollBack();
    }

    /**
     * @return void
     */
    public function succeed()
    {
        $this->getStateObject()->succeed();
    }


    /**
     * @return void
     */
    public function failed()
    {
        $this->getStateObject()->failed();
    }

    /**
     * @return Migration\StateBase
     *
     * @throws UndefinedClassForState
     */
    private function getStateObject()
    {
        if (!$this->stateObject || $this->stateObject->getStatusId() !== $this->obj_status_did) {
            if (!($stateClassName = $this->statusIdToStateClassMap[$this->obj_status_did])) {
                $message = "Undefined class for state={$this->obj_status_did} of migration_id={$this->obj_id}";
                throw new UndefinedClassForState($message);
            }

            $this->stateObject = new $stateClassName($this);
        }

        return $this->stateObject;
    }
}
