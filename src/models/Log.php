<?php
namespace whotrades\rds\models;

use whotrades\rds\models\User\User;
use yii\data\ActiveDataProvider;
use whotrades\rds\components\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "rds.log".
 *
 * The followings are the available columns in table 'rds.log':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property int $log_user_id
 * @property string $log_text
 * @property User $user
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
            array(['obj_status_did', 'log_user_id'], 'number'),
            array(['log_text'], 'safe'),
        // The following rule is used by search().
        // @todo Please remove those attributes that should not be searched.
            array(['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'log_user_id', 'log_text'], 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'Obj',
            'obj_created' => 'Время',
            'obj_modified' => 'Obj Modified',
            'obj_status_did' => 'Obj Status Did',
            'log_user_id' => 'Пользователь',
            'log_text' => 'Событие',
        );
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = self::find()->andWhere(array_filter(['obj_id' => $params['obj_id']]));
        $query->filterWhere(['like', 'log_text', $params['log_text']]);

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
        $log = new self();
        $log->log_text = $text;
        if ($user && $userObj = User::findOne(['username' => $user])) {
            $log->log_user_id = $userObj->id;
        } else {
            $log->log_user_id = empty(\Yii::$app->user) || \Yii::$app->user->isGuest ? null : \Yii::$app->user->id;
        }

        if (!$log->save()) {
            throw new \Exception("Can't create log request: " . json_encode($log->errors));
        }

        \Yii::$app->webSockets->send('logUpdated', []);

        return $log;
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'log_user_id']);
    }
}
