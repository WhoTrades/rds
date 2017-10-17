<?php
namespace whotrades\rds\models;

use whotrades\rds\components\ActiveRecord;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "rds.jira_use".
 *
 * The followings are the available columns in table 'rds.jira_use':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $jira_use_from_build_tag
 * @property string $jira_use_to_build_tag
 * @property string $jira_use_initiator_user_name
 */
class JiraUse extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.jira_use';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array(['jira_use_from_build_tag', 'jira_use_to_build_tag', 'jira_use_initiator_user_name'], 'required'),
            array(['obj_status_did'], 'integer'),
            array(['jira_use_from_build_tag', 'jira_use_to_build_tag'], 'string', 'max' => 64),
            array(['obj_id', 'obj_created', 'obj_modified', 'obj_status_did', 'jira_use_from_build_tag', 'jira_use_to_build_tag'], 'safe', 'on' => 'search'),
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
            'jira_use_from_build_tag' => 'Jira Use From Build Tag',
            'jira_use_to_build_tag' => 'Jira Use To Build Tag',
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

        $dataProvider = new ActiveDataProvider(['query' => $query]);
        $this->load($params, 'search');

        return $dataProvider;
    }
}
