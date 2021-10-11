<?php

use \whotrades\rds\migrations\base;

/**
 * Class m211006_135705_release_request_additional_json
 */
class m211006_135705_release_request_additional_json extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("ALTER TABLE rds.release_request ADD COLUMN rr_additional jsonb NOT NULL DEFAULT '{}'::jsonb;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("ALTER TABLE rds.release_request DROP COLUMN rr_additional;");

        return false;
    }

}
