<?php

/**
 * This is the model class for table "rds.developer".
 *
 * The followings are the available columns in table 'rds.developer':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $whotrades_email
 * @property string $finam_email
 *
 * The followings are the available model relations:
 * @property JiraFeature[] $jiraFeatures
 */
class Developer extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.developer';
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
            array('obj_created, obj_modified, whotrades_email, finam_email', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('whotrades_email, finam_email', 'length', 'max'=>64),
            array('whotrades_email, finam_email', 'email'),
            array('whotrades_email', 'match', 'pattern' => '~^\w+@whotrades.org$~', 'message' => "WhoTrades email должен быть в зоне @whotrades.org, например qr@whotrades.org",),
            array('finam_email', 'match', 'pattern' => '~^\w+@corp.finam.ru$~', 'message' => "WhoTrades email должен быть в зоне @corp.finam.ru, например lkutuzoff@corp.finam.ru",),
            array('whotrades_email, finam_email', 'unique'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, whotrades_email, finam_email', 'safe', 'on'=>'search'),
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
            'jiraFeatures' => array(self::HAS_MANY, 'JiraFeature', 'jf_developer_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'obj_id' => 'ID',
            'obj_created' => 'Obj Created',
            'obj_modified' => 'Obj Modified',
            'obj_status_did' => 'Obj Status Did',
            'whotrades_email' => 'Whotrades Email',
            'finam_email' => 'Finam Email',
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
        $criteria->compare('whotrades_email',$this->whotrades_email,true);
        $criteria->compare('finam_email',$this->finam_email,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
            'pagination' => [
                'pageSize' => 100,
            ],
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Developer the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public static function getByWhoTradesEmail($whoTradesEmail)
    {
        return static::model()->findByAttributes(['whotrades_email' => $whoTradesEmail]);
    }

    public function getTitle()
    {
        return "Разработчик $this->whotrades_email, корпоративная почта - $this->finam_email";
    }

    public function forList(){
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $list = array('' => " - Разработчик - ");
        $c=new CDbCriteria;
        $c->order = 'finam_email';
        foreach ($this->findAll($c) as $val)
            $list[$val->obj_id] = $val->finam_email;

        return $cache = $list;
    }
}
