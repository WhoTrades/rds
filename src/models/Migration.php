<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models;

use Yii;
use whotrades\rds\helpers\MigrationInterface as MigrationHelperInterface;
use yii\data\ActiveDataProvider;
use whotrades\rds\models\Migration\Exception\UndefinedClassForState;

/**
 * This is the model class for table "rds.migration".
 *
 * The followings are the available columns in table 'rds.migration':
 *
 * {@inheritDoc}
 */
class Migration extends MigrationBase
{
    const TYPE_ID_PRE  = 1;
    const TYPE_ID_POST = 2;
    const TYPE_ID_HARD = 3;

    const TYPE_PRE  = 'pre';
    const TYPE_POST = 'post';
    const TYPE_HARD = 'hard';

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
     * @return string[]
     */
    public static function getTypeIdToNameMap()
    {
        return [
            self::TYPE_ID_PRE  => self::TYPE_PRE,
            self::TYPE_ID_POST => self::TYPE_POST,
            self::TYPE_ID_HARD => self::TYPE_HARD,
        ];
    }

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
                    ->orderBy(['obj_created' => SORT_DESC, 'migration_name' => SORT_DESC])
            ]
        );
        $this->load($params, 'search');

        return $dataProvider;
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
        if ($this->migration_type === self::TYPE_POST) {
            return $this->getMigrationHelper()->getWaitingDays($this);
        }

        return 0;
    }

    /**
     * @return bool
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
     * @return void
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

    /**
     * @return MigrationHelperInterface
     */
    private function getMigrationHelper()
    {
        return new Yii::$app->params['migrationHelperClass'];
    }
}
