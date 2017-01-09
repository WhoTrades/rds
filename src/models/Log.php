<?php

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
    public function tableName()
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
            array('obj_created, obj_modified', 'required'),
            array('obj_status_did', 'numerical', 'integerOnly'=>true),
            array('log_user', 'length', 'max'=>128),
            array('log_text', 'safe'),
        // The following rule is used by search().
        // @todo Please remove those attributes that should not be searched.
            array('obj_id, obj_created, obj_modified, obj_status_did, log_user, log_text', 'safe', 'on'=>'search'),
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
            'log_user' => 'Log User',
            'log_text' => 'Log Text',
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
        $criteria->compare('log_user',$this->log_user,true);
        $criteria->compare('log_text',$this->log_text,true);

        if (empty($_GET['Log_sort'])) {
            $criteria->order = 'obj_created desc, obj_id desc';
        }
        $criteria->limit = 100;

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
     * @return Log the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public static function createLogMessage($text, $user = null)
    {
        if ($user == null) {
            $user = \Yii::app()->user->name;
        }
        $log = new self();
        $log->log_text = $text;
        $log->log_user = $user;
        if (!$log->save()) {
            throw new Exception("Can't create log request: ".json_encode($log->errors));
        }

        Yii::app()->webSockets->send('logUpdated', []);

        return $log;
    }
}
