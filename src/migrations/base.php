<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 *
 * Base class for migrations for using DB role db_admin
 */
namespace whotrades\rds\migrations;

use yii\db\Migration;

class base extends Migration
{
    /**
     */
    public function init()
    {
        $this->db = 'db_admin';
        parent::init();
    }
}
