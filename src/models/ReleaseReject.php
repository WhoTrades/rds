<?php
namespace whotrades\rds\models;

use whotrades\rds\components\ActiveRecord;
use whotrades\rds\models\User\User;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "rds.release_reject".
 *
 * The followings are the available columns in table 'rds.release_reject':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $rr_comment
 * @property string $rr_project_obj_id
 * @property string $rr_release_version
 * @property User $user
 */
class ReleaseReject extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.release_reject';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['rr_user_id', 'rr_comment', 'rr_project_obj_id', 'rr_release_version'], 'required'),
            array(['obj_status_did'], 'number'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'rr_user_id', 'rr_comment', 'rr_project_obj_id', 'rr_release_version'], 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'Номер',
            'obj_created' => 'Дата создания',
            'obj_modified' => 'Modified',
            'obj_status_did' => 'Status Did',
            'rr_user_id' => 'Пользователь',
            'rr_comment' => 'Комментарий',
            'rr_project_obj_id' => 'Номер проекта',
            'rr_release_version' => 'Версия',
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
        $dataProvider = new ActiveDataProvider(['query' => self::find()]);
        $this->load($params, 'search');

        return $dataProvider;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "запрет релиза #$this->obj_id {$this->project->project_name} v.$this->rr_release_version ($this->rr_comment)";
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['obj_id' => 'rr_project_obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'rr_user_id']);
    }
}
