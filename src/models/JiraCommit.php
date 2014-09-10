
<?php

/**
 * This is the model class for table "rds.jira_commit".
 *
 * The followings are the available columns in table 'rds.jira_commit':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $jira_commit_build_tag
 * @property string $jira_commit_hash
 * @property string $jira_commit_author
 * @property string $jira_commit_comment
 * @property string $jira_commit_ticket
 * @property string $jira_commit_project
 */
class JiraCommit extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.jira_commit';
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
                array('obj_created, obj_modified, jira_commit_build_tag, jira_commit_hash, jira_commit_author, jira_commit_comment, jira_commit_ticket, jira_commit_project', 'required'),
                array('obj_status_did', 'numerical', 'integerOnly'=>true),
                array('jira_commit_hash', 'length', 'max'=>40),
                array('jira_commit_author', 'length', 'max'=>64),
                array('jira_commit_comment', 'length', 'max'=>256),
                array('jira_commit_ticket', 'length', 'max'=>16),
                array('jira_commit_project', 'length', 'max'=>8),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
                array('obj_id, obj_created, obj_modified, obj_status_did, jira_commit_build_tag, jira_commit_hash, jira_commit_author, jira_commit_comment, jira_commit_ticket, jira_commit_project', 'safe', 'on'=>'search'),
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
                'jira_commit_build_tag' => 'Build tag',
                'jira_commit_hash' => 'Jira Commit Hash',
                'jira_commit_author' => 'Jira Commit Author',
                'jira_commit_comment' => 'Jira Commit Comment',
                'jira_commit_ticket' => 'Jira Commit Ticket',
                'jira_commit_project' => 'Jira Commit Project',
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
        $criteria->compare('jira_commit_build_tag',$this->jira_commit_build_tag,true);
        $criteria->compare('jira_commit_hash',$this->jira_commit_hash,true);
        $criteria->compare('jira_commit_author',$this->jira_commit_author,true);
        $criteria->compare('jira_commit_comment',$this->jira_commit_comment,true);
        $criteria->compare('jira_commit_ticket',$this->jira_commit_ticket,true);
        $criteria->compare('jira_commit_project',$this->jira_commit_project,true);

        return new CActiveDataProvider($this, array(
                'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return JiraCommit the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}