<?php
namespace app\models;

use app\components\ActiveRecord;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "rds.worker".
 *
 * The followings are the available columns in table 'rds.worker':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $worker_name
 *
 * The followings are the available model relations:
 * @property Project2worker[] $project2workers
 */
class Worker extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.worker';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['worker_name'], 'required'),
            array(['obj_status_did'], 'number'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'worker_name'], 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return Project2Worker[]
     */
    public function getProject2workers()
    {
        return $this->hasMany(Project2worker::class, ['project_obj_id' => 'obj_id'])->all();
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
            'worker_name' => 'Worker Name',
        );
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = self::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        return $dataProvider;
    }

    /**
     * @return array
     */
    public function forList()
    {
        $list = array('' => " - Worker - ");
        foreach ($this->find()->all() as $val) {
            $list[$val->obj_id] = $val->worker_name;
        }

        return $list;
    }
}
