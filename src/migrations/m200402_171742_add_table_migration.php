<?php

use \whotrades\rds\migrations\base;

/**
 * Class m200402_171742_add_table_migration
 */
class m200402_171742_add_table_migration extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('CREATE TABLE "rds"."migration" (
                "obj_id" bigserial,
                "obj_created" timestamp with time zone DEFAULT now() NOT NULL,
                "obj_modified" timestamp with time zone DEFAULT now() NOT NULL,
                "obj_status_did" int2 DEFAULT 5 NOT NULL,
                "migration_name" varchar COLLATE "default" NOT NULL,
                "migration_type" int2 NOT NULL,
                "migration_project_obj_id" int8 NOT NULL,
                "migration_release_request_obj_id" int8 NOT NULL,
                "migration_ticket" character varying(16) COLLATE "default",
                "migration_log" text
            )
            INHERITS ("obj_base") 
            WITH (OIDS=FALSE)
        ');

        $this->execute('ALTER TABLE rds.migration ADD PRIMARY KEY (obj_id)');
        $this->execute('CREATE UNIQUE INDEX u_migration_name_and_project ON rds.migration (migration_name, migration_project_obj_id)');
        $this->execute("COMMENT ON COLUMN rds.migration.obj_status_did IS 'ag: @see \\whotrades\\rds\\models\\Migration::STATUS_*'");
        $this->execute("COMMENT ON COLUMN rds.migration.migration_type IS 'ag: @see \\whotrades\\rds\\models\\Migration::TYPE_*'");

        $this->execute('ALTER TABLE rds.migration
                            ADD CONSTRAINT rds_migration_project_obj_id
                            FOREIGN KEY (migration_project_obj_id)
                            REFERENCES rds.project (obj_id) MATCH SIMPLE
                            ON UPDATE RESTRICT
                            ON DELETE CASCADE;');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('DROP TABLE "rds"."migration"');
    }
}
