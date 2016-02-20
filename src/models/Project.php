<?php

/**
 * This is the model class for table "rds.project".
 *
 * The followings are the available columns in table 'rds.project':
 *
 * @property string           $obj_id
 * @property string           $obj_created
 * @property string           $obj_modified
 * @property integer          $obj_status_did
 * @property string           $project_name
 * @property string           $project_config
 * @property string           $project_build_version
 * @property array            $project_build_subversion
 * @property string           $project_current_version
 * @property string           $project_pre_migration_version
 * @property string           $project_post_migration_version
 * @property string           $project_notification_email
 * @property string           $project_notification_subject
 * @property ReleaseRequest[] $releaseRequests
 * @property ProjectConfig[]  $projectConfigs
 */
class Project extends ActiveRecord
{
    const STATUS_NEW = 'new';

    public $projectBuildSubversionArray;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'rds.project';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['obj_created, obj_modified, project_name', 'required'],
            ['obj_status_did', 'numerical', 'integerOnly' => true],
            ['project_notification_email', 'email'], ['project_config', 'safe'],
            
            ['project_notification_email, project_notification_subject', 'length', 'max' => 64],
            ['obj_id, obj_created, obj_modified, obj_status_did, project_name, project_build_version, project_current_version', 'safe', 'on' => 'search'],
        ];
    }

    /**
     * project_build_subversion - это hstore, и его нужно развернуть в обычный php массив
     */
    public function afterFind()
    {
        $this->projectBuildSubversionArray = eval('return array(' . $this->project_build_subversion . ');');
    }

    /**
     * Возвращает новую версию билда для проекта
     *
     * @see http://jira/browse/WTS-376
     * @param string $releaseVersion
     *
     * @return string
     */
    public function getNextVersion($releaseVersion)
    {
        $code = '00';
        $buildNumber = isset($this->projectBuildSubversionArray[$releaseVersion]) ? $this->projectBuildSubversionArray[$releaseVersion] : 1;

        // an: Сделал вывод с ведущими нулями что бы лексикографическая вортировка валидно работала для наших версий
        // (предполагаем что внутри одноо релиза не будет более 1000 билдов)
        return implode(".", [
            $releaseVersion,
            $code,
            sprintf('%03d', $buildNumber),
            $this->project_build_version,
        ]);
    }

    /**
     * @param int $releaseVersion
     */
    public function incrementBuildVersion($releaseVersion)
    {
        $releaseVersion = (int) $releaseVersion;
        $this->project_build_version;
        $subversion = isset($this->projectBuildSubversionArray[$releaseVersion]) ? $this->projectBuildSubversionArray[$releaseVersion] + 1 : 2;

        $sql = "UPDATE {$this->tableName()}
            SET project_build_version=$this->project_build_version+1, project_build_subversion = project_build_subversion || '$releaseVersion=>$subversion'::hstore
            WHERE obj_id=$this->obj_id";

        Yii::app()->db->createCommand($sql)->execute();
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            'releaseRequests' => [self::HAS_MANY, 'ReleaseRequest', 'rr_project_obj_id'],
            'releaseRejects'  => [self::HAS_MANY, 'ReleaseReject',  'rr_project_obj_id'],
            'project2workers' => [self::HAS_MANY, 'Project2worker', 'project_obj_id'],
            'projectConfigs'  => [self::HAS_MANY, 'ProjectConfig',  'pc_project_obj_id'],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'obj_id' => 'ID',
            'obj_created' => 'Created',
            'obj_modified' => 'Modified',
            'obj_status_did' => 'Status Did',
            'project_name' => 'Project Name',
            'project_notification_email' => 'Email оповещеиня о выкладке',
            'project_notification_subject' => 'Тема оповещения о выкладке',
        ];
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

        $criteria = new CDbCriteria();

        $criteria->compare('obj_id', $this->obj_id);
        $criteria->compare('obj_created', $this->obj_created, true);
        $criteria->compare('obj_modified', $this->obj_modified, true);
        $criteria->compare('obj_status_did', $this->obj_status_did);
        $criteria->compare('project_name', $this->project_name, true);
        $criteria->compare('project_current_version', $this->project_current_version, true);

        return new CActiveDataProvider($this, [
            'criteria' => $criteria,
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
    }

    /**
     * Возвращает список проектов для вывода в <select>
     * @return array
     */
    public function forList()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $list = ['' => " - Project - "];

        $c = new CDbCriteria();
        $c->order = 'project_name';
        foreach ($this->findAll($c) as $val) {
            $list[$val->obj_id] = $val->project_name;
        }

        return $cache = $list;
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     *
     * @param string $className active record class name.
     *
     * @return Project the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param string $version
     * @throws Exception
     */
    public function updateCurrentVersion($version)
    {
        $this->project_current_version = $version;
        $this->save(false);
    }
}
