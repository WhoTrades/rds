<?php
namespace app\models;

use yii\data\ActiveDataProvider;
use app\components\ActiveRecord;

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
 * @property Project2Worker[] $project2workers
 */
class Project extends ActiveRecord
{
    const STATUS_NEW = 'new';

    public $projectBuildSubversionArray;

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'rds.project';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['project_name', 'required'],
            ['obj_status_did', 'number'],
            ['project_notification_email', 'email'],
            ['project_config', 'safe'],
            [['project_notification_email', 'project_notification_subject'], 'string', 'max' => 64],
            [['obj_id', 'obj_created', 'obj_modified', 'obj_status_did, project_name', 'project_build_version', 'project_current_version'], 'safe', 'on' => 'search'],
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

        \Yii::$app->db->createCommand($sql)->execute();
    }

    /**
     * @return ReleaseRequest[]
     */
    public function getReleaseRequests()
    {
        return $this->hasMany(ReleaseRequest::class, ['rr_project_obj_id' => 'obj_id'])->all();
    }

    /**
     * @return ReleaseReject[]
     */
    public function getReleaseRejects()
    {
        return $this->hasMany(ReleaseRequest::class, ['rr_project_obj_id' => 'obj_id'])->all();
    }

    /**
     * @return Project2Worker[]
     */
    public function getProject2workers()
    {
        return $this->hasMany(Project2worker::class, ['project_obj_id' => 'obj_id'])->all();
    }

    /**
     * @return ProjectConfig[]
     */
    public function getProjectConfigs()
    {
        return $this->hasMany(ProjectConfig::class, ['pc_project_obj_id' => 'obj_id'])->all();
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
            'project_name' => 'Проект',
            'project_notification_email' => 'Email оповещеиня о выкладке',
            'project_notification_subject' => 'Тема оповещения о выкладке',
        ];
    }

    /**
     * Возвращает список проектов для вывода в <select>
     * @return array
     */
    public static function forList()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $list = ['' => " - Project - "];

        $projects = static::find()->orderBy('project_name')->all();
        foreach ($projects as $val) {
            $list[$val->obj_id] = $val->project_name;
        }

        return $cache = $list;
    }

    /**
     * @param string $version
     */
    public function updateCurrentVersion($version)
    {
        $this->project_current_version = $version;
        $this->save(false);
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = self::find()->filterWhere($params);

        $dataProvider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 100]]);
        $this->load($params, 'search');

        return $dataProvider;
    }

    /**
     * Возвращает ссылку в stash на исходный код миграции
     *
     * @param string $migration
     * @param string $type - pre|post|hard
     *
     * @return string
     */
    public function getMigrationUrl($migration, $type)
    {
        $config = \Yii::$app->params['projectMigrationUrlMask'];
        if (isset($config[$this->project_name])) {
            return $config[$this->project_name]($migration, $this->project_name, $type);
        } else {
            return $config['*']($migration, $this->project_name, $type);
        }
    }
}
