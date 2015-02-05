
<?php

/**
 * This is the model class for table "rds.jira_feature".
 *
 * The followings are the available columns in table 'rds.jira_feature':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $jf_developer_id
 * @property string $jf_ticket
 * @property string $jf_status
 * @property string $jf_branch
 * @property string $jf_last_merge_request_to_master_time
 * @property string $jf_last_merge_request_to_develop_time
 * @property string $jf_last_merge_request_to_staging_time
 * @property string $jf_affected_repositories
 *
 * The followings are the available model relations:
 * @property Developer $jfDeveloper
 */
class JiraFeature extends CActiveRecord
{
    const STATUS_IN_PROGRESS    = 'progress';
    const STATUS_CHECKING       = 'checking';
    const STATUS_CLOSED         = 'closed';
    const STATUS_REMOVING       = 'removing';
    const STATUS_REMOVED        = 'removed';
    const STATUS_PAUSED         = 'paused';
    const STATUS_CANCELLED      = 'cancelled';

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.jira_feature';
    }

    public function afterConstruct() {
        if ($this->isNewRecord) {
            $this->obj_created = date("r");
            $this->obj_modified = date("r");
        }
        return parent::afterConstruct();
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('obj_created, obj_modified, jf_developer_id, jf_ticket, jf_branch, jf_affected_repositories', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('jf_ticket, jf_status', 'length', 'max'=>16),
            array('jf_branch', 'length', 'max'=>64),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, jf_developer_id, jf_ticket, jf_status, jf_branch, jf_last_merge_request_to_master_time, jf_last_merge_request_to_develop_time', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'developer' => array(self::BELONGS_TO, 'Developer', 'jf_developer_id'),
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
            'jf_developer_id' => 'Jf Developer',
            'jf_ticket' => 'Jf Ticket',
            'jf_status' => 'Jf Status',
            'jf_branch' => 'Jf Branch',
            'jf_last_merge_request_to_master_time' => 'Last merge to master request',
            'jf_last_merge_request_to_develop_time' => 'Last merge to develop request',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('obj_id',$this->obj_id,true);
        $criteria->compare('obj_created',$this->obj_created,true);
        $criteria->compare('obj_modified',$this->obj_modified,true);
        $criteria->compare('obj_status_did',$this->obj_status_did);
        $criteria->compare('jf_developer_id',$this->jf_developer_id,true);
        $criteria->compare('jf_ticket',$this->jf_ticket,true);
        $criteria->compare('jf_status',$this->jf_status,true);
        $criteria->compare('jf_branch',$this->jf_branch,true);
        $criteria->compare('jf_last_merge_request_to_master_time',$this->jf_last_merge_request_to_master_time,true);
        $criteria->compare('jf_last_merge_request_to_develop_time',$this->jf_last_merge_request_to_develop_time,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return JiraFeature the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function getNonClosedJiraFeatures($developerId, $exceptTicket)
    {
        $c = new CDbCriteria();
        $c->compare('jf_developer_id', $developerId);
        $c->compare('jf_status', '<>'.self::STATUS_CLOSED);
        $c->compare('jf_status', '<>'.self::STATUS_REMOVED);
        $c->compare('jf_status', '<>'.self::STATUS_REMOVING);
        $c->compare('jf_ticket', '<>'.$exceptTicket);

        return self::model()->findAll($c);
    }

    public function resetMergeConditions()
    {
        $this->jf_last_merge_request_to_develop_time = null;
        $this->jf_last_merge_request_to_master_time = null;
        $this->jf_last_merge_request_to_staging_time = null;
    }

    public function addAffectedRepository($repository)
    {
        $list = json_decode($this->jf_affected_repositories, true);
        $list[] = $repository;
        $list = array_unique($list);
        sort($list);
        $this->jf_affected_repositories = json_encode($list);
    }
}
