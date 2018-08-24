<?php
namespace whotrades\rds\models;

use Yii;
use yii\data\ActiveDataProvider;
use whotrades\rds\components\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "rds.post_migration".
 *
 * The followings are the available columns in table 'rds.post_migration':
 *
 * @property string             $obj_id
 * @property string             $obj_created
 * @property string             $obj_modified
 * @property integer            $obj_status_did
 * @property string             $pm_name
 * @property string             $pm_status
 * @property string             $pm_project_obj_id
 * @property string             $pm_release_request_obj_id
 * @property string             $pm_log
 *
 * @property Project            $project
 * @property ReleaseRequest     $releaseRequest
 */
class PostMigration extends ActiveRecord
{
    const STATUS_APPLIED = 1;
    const STATUS_FAILED = 3;
    const STATUS_STARTED = 4;
    const STATUS_PENDING = 5;

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.post_migration';
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
            'pm_name' => 'Migration',
            'pm_status' => 'Migration',
            'pm_project_obj_id' => 'ID проекта',
            'pm_release_request_obj_id' => 'ID ReleaseRequest',
            'project.project_name' => 'Project',
            'releaseRequest.rr_build_version' => 'Release Request',
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
        $dataProvider = new ActiveDataProvider(['query' => self::find()->orderBy(['obj_created' => SORT_DESC])]);
        $this->load($params, 'search');

        return $dataProvider;
    }

    /**
     * @return ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['obj_id' => 'pm_project_obj_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getReleaseRequest()
    {
        return $this->hasOne(ReleaseRequest::class, ['obj_id' => 'pm_release_request_obj_id'])->orWhere(['IS NOT', 'obj_status_did', null]);
    }
}
