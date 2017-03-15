<?php
use \Cronjob\RestorePoint\RestorePointInterface;
use \Cronjob\RestorePoint\File;
use \Cronjob\RestorePoint\Postgres;
use \Cronjob\RestorePoint\Composite;

class YiiPgq extends PgQ\Cronjob\RequestHandler\Pgq
{
    /**
     * YiiPgq constructor.
     *
     * @param ServiceBase_IDebugLogger $debugLogger
     */
    public function __construct($debugLogger)
    {
        // an: Инициализируем ядро Yii
        YiiBridge::init($debugLogger);

        parent::__construct($debugLogger);
    }

    /**
     * @param string $id
     * @return RestorePointInterface
     */
    public function getRestorePoint($id)
    {
        /** @var $db CDbConnection */
        $db = Yii::app()->db;

        $point = new Postgres($db->connectionString, $db->username, $db->password);

        $point->setRestorePointId($id);

        return $point;
    }
}
