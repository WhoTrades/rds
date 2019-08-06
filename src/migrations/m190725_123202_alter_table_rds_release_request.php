<?php

use \whotrades\rds\migrations\base;

/**
 * Class m190725_123202_alter_table_rds_release_request
 */
class m190725_123202_alter_table_rds_release_request extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('rds.release_request', 'rr_last_error_text', 'text');
        $this->execute('UPDATE rds.release_request SET rr_last_error_text = rr_use_text');
    }

    public function safeDown()
    {
        $this->dropColumn('rds.release_request', 'rr_last_error_text');
    }
}
