<?php

use yii\db\Migration;

/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 *
 * Base class for migrations for using DB role db_admin
 */
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
