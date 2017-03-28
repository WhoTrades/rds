<?php
namespace app\models;

use yii\data\ActiveDataProvider;
use app\components\ActiveRecord;

/**
 * This is the model class for table "rds.log".
 *
 * The followings are the available columns in table 'rds.log':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $log_user
 * @property string $log_text
 */
class Log extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.log';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['obj_status_did'], 'number'),
            array(['log_user'], 'string', 'max' => 128),
            array(['log_text'], 'safe'),
        // The following rule is used by search().
        // @todo Please remove those attributes that should not be searched.
            array(['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'log_user', 'log_text'], 'safe', 'on' => 'search'),
        );
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
            'log_user' => 'Log User',
            'log_text' => 'Log Text',
        );
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = self::find()->filterWhere($params);

        if (empty($params['Log_sort'])) {
            $query->orderBy('obj_created desc, obj_id desc');
        }

        $dataProvider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 100]]);
        $this->load($params, 'search');

        return $dataProvider;
    }

    /**
     * @param string $text
     * @param string $user
     * @return Log
     */
    public static function createLogMessage(string $text, string $user = null)
    {

        if ($user == null) {
            $user = \Yii::$app->user->getIdentity()->username;
        }
        $log = new self();
        $log->log_text = $text;
        $log->log_user = $user;
        if (!$log->save()) {
            throw new \Exception("Can't create log request: " . json_encode($log->errors));
        }

        \Yii::$app->webSockets->send('logUpdated', []);

        return $log;
    }
}
