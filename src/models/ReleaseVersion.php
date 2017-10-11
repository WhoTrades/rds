<?php
namespace whotrades\rds\models;

use yii\data\ActiveDataProvider;
use whotrades\rds\components\ActiveRecord;

/**
 * This is the model class for table "rds.release_version".
 *
 * The followings are the available columns in table 'rds.release_version':
 * @property string $obj_id
 * @property string $obj_created
 * @property string $obj_modified
 * @property integer $obj_status_did
 * @property string $rv_version
 * @property string $rv_name
 */
class ReleaseVersion extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.release_version';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array(['rv_version', 'rv_name'], 'required'),
            array(['obj_status_did'], 'number'),
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
            'rv_version' => 'Rv Version',
            'rv_name' => 'Rv Name',
        );
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = static::find();

        if ($this->load($params)) {
            $query->andFilterWhere($this->attributes);
        }

        return new ActiveDataProvider([
            'query' => $query,
        ]);
    }

    /**
     * @return array
     */
    public static function forList()
    {
        $list = array();
        foreach (static::find()->all() as $val) {
            $list[$val->rv_version] = $val->rv_name . " - " . $val->rv_version;
        }

        return $list;
    }
}
