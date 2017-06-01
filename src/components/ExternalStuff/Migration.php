<?php
namespace app\components\ExternalStuff;

use Yii;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class Migration extends \MigrationSystem\components\DbMigration
{
    const DB = self::DSN_DB4;
    /**
     * @var string
     */
    protected $tableOptions;
    protected $restrict = 'RESTRICT';
    protected $cascade = 'CASCADE';
    protected $dbType;
    

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        switch ($this->db->driverName) {
            case 'mysql':
                $this->tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
                $this->dbType = 'mysql';
                break;
            case 'pgsql':
                $this->tableOptions = null;
                $this->dbType = 'pgsql';
                break;
            case 'dblib':
            case 'mssql':
            case 'sqlsrv':
                $this->restrict = 'NO ACTION';
                $this->tableOptions = null;
                $this->dbType = 'sqlsrv';
                break;
            default:
                throw new \RuntimeException('Your database is not supported!');
        }
    }

    /**
     * @param string $table
     * @param string $column
     */
    public function dropColumnConstraints($table, $column)
    {
        $table = Yii::$app->db->schema->getRawTableName($table);
        $cmd = Yii::$app->db->createCommand('SELECT name FROM sys.default_constraints
                                WHERE parent_object_id = object_id(:table)
                                AND type = \'D\' AND parent_column_id = (
                                    SELECT column_id 
                                    FROM sys.columns 
                                    WHERE object_id = object_id(:table)
                                    and name = :column
                                )', [ ':table' => $table, ':column' => $column ]);
                                
        $constraints = $cmd->queryAll();
        foreach ($constraints as $c) {
            $this->execute('ALTER TABLE ' . Yii::$app->db->quoteTableName($table) . ' DROP CONSTRAINT ' . Yii::$app->db->quoteColumnName($c['name']));
        }
    }
}
