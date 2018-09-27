<?php
namespace whotrades\rds\models;

use whotrades\rds\models\User\User;
use yii\data\ActiveDataProvider;
use whotrades\rds\components\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "rds.project_config_history".
 *
 * The followings are the available columns in table 'rds.project_config_history':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $pch_project_obj_id
 * @property int $pch_user_id
 * @property string $pch_config
 * @property string $pch_filename
 * @property string $pch_log
 *
 * The followings are the available model relations:
 * @property Project $project
 * @property User $user
 */
class ProjectConfigHistory extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.project_config_history';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['pch_project_obj_id', 'pch_user_id'], 'required'),
            array(['obj_status_did', 'pch_user_id'], 'number'),
            array(['pch_config'], 'safe'),
            array(['pch_log'], 'safe'),
        );
    }

    /**
     * @return ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['obj_id' => 'pch_project_obj_id']);
    }

    /**
     * @param array $params
     * @param int $id
     * @return ActiveDataProvider
     */
    public function search(array $params, $id = null)
    {
        $query = static::find()->filterWhere(['pch_project_obj_id' => $id]);

        if ($this->load($params)) {
            $query->andFilterWhere($this->attributes);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['obj_created' => SORT_DESC]],
        ]);
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
            'obj_status_did' => 'Obj Status Did',
            'pch_project_obj_id' => 'Pch Project Obj',
            'pch_user_id' => 'Pch User',
            'pch_config' => 'Pch Config',
        );
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'pch_user_id']);
    }
}
