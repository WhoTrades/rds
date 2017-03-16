<?php
use \Cronjob\RestorePoint\RestorePointInterface;
use \Cronjob\RestorePoint\Postgres;
use yii\db\Connection;

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
        /** @var $db Connection */
        $db = Yii::$app->db;

        $point = new Postgres($db->dsn, $db->username, $db->password);

        $point->setRestorePointId($id);

        return $point;
    }
}
