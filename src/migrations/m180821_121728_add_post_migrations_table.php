<?php

use yii\db\Migration;

/**
 * Class m180821_121728_add_post_migrations_table
 */
class m180821_121728_add_post_migrations_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('CREATE TABLE "rds"."post_migration" (
                "obj_id" bigserial,
                "obj_created" timestamp with time zone DEFAULT now() NOT NULL,
                "obj_modified" timestamp with time zone DEFAULT now() NOT NULL,
                "obj_status_did" int2 DEFAULT 1 NOT NULL,
                "pm_name" varchar COLLATE "default" NOT NULL,
                "pm_status" int2 DEFAULT 5 NOT NULL,
                "pm_project_obj_id" int8 NOT NULL,
                "pm_release_request_obj_id" int8 NOT NULL,
                "pm_log" text
            )
            INHERITS ("obj_base") 
            WITH (OIDS=FALSE)
        ');

        $this->execute('ALTER TABLE rds.post_migration ADD PRIMARY KEY (obj_id)');
        $this->execute('CREATE UNIQUE INDEX u_pm_name_and_project ON rds.post_migration (pm_name, pm_project_obj_id)');
        $this->execute("COMMENT ON COLUMN rds.post_migration.pm_status IS 'ag: @see \\whotrades\\rds\\models\\PostMigration::STATUS_*'");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('DROP TABLE "rds"."post_migration"');
    }
}
