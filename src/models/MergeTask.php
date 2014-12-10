<?php

/**
 * This is the model class for table "rds.merge_task".
 *
 * The followings are the available columns in table 'rds.merge_task':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $mt_source_branch
 * @property string $mt_target_branch
 * @property string $mt_ticket
 * @property string $mt_next_transition
 */
class MergeTask extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.merge_task';
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
            array('obj_created, obj_modified, mt_source_branch, mt_target_branch, mt_ticket, mt_next_transition', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('mt_source_branch, mt_target_branch, mt_ticket, mt_next_transition', 'length', 'max'=>64),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, mt_source_branch, mt_target_branch, mt_ticket, mt_next_transition', 'safe', 'on'=>'search'),
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
            'mt_source_branch' => 'Mt Source Branch',
            'mt_target_branch' => 'Mt Target Branch',
            'mt_ticket' => 'Mt Ticket',
            'mt_next_transition' => 'Mt Next Transition',
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
        $criteria->compare('mt_source_branch',$this->mt_source_branch,true);
        $criteria->compare('mt_target_branch',$this->mt_target_branch,true);
        $criteria->compare('mt_ticket',$this->mt_ticket,true);
        $criteria->compare('mt_next_transition',$this->mt_next_transition,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return MergeTask the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}