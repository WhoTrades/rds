create schema rds;

CREATE TABLE rds."user" (
    id integer NOT NULL,
    username character varying(50) NOT NULL,
    email character varying(255) NOT NULL,
    password_hash character varying(60) NOT NULL,
    auth_key character varying(32) NOT NULL,
    confirmed_at integer,
    unconfirmed_email character varying(255) DEFAULT NULL::character varying,
    blocked_at integer,
    registration_ip character varying(45),
    created_at integer NOT NULL,
    updated_at integer NOT NULL,
    flags integer DEFAULT 0 NOT NULL,
    last_login_at integer,
    phone character varying(64) DEFAULT NULL::character varying
);
CREATE SEQUENCE user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER TABLE ONLY rds."user" ALTER COLUMN id SET DEFAULT nextval('user_id_seq'::regclass);
ALTER TABLE ONLY rds."user" ADD CONSTRAINT user_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX rds_user_unique_email ON rds."user" USING btree (email);
CREATE UNIQUE INDEX rds_user_unique_username ON rds."user" USING btree (username);


CREATE TABLE rds.session (
  id char(128) NOT NULL PRIMARY KEY,
  expire bigint,
  data bytea
);
CREATE TABLE "rds"."profile" (
"user_id" int4 NOT NULL,
"name" varchar(255) COLLATE "default" DEFAULT NULL::character varying,
"public_email" varchar(255) COLLATE "default" DEFAULT NULL::character varying,
"gravatar_email" varchar(255) COLLATE "default" DEFAULT NULL::character varying,
"gravatar_id" varchar(32) COLLATE "default" DEFAULT NULL::character varying,
"location" varchar(255) COLLATE "default" DEFAULT NULL::character varying,
"website" varchar(255) COLLATE "default" DEFAULT NULL::character varying,
"bio" text COLLATE "default",
"timezone" varchar(40) COLLATE "default" DEFAULT NULL::character varying
)
WITH (OIDS=FALSE);

CREATE TABLE "rds"."obj_base" (
"obj_id" int8 NOT NULL,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL
)
WITH (OIDS=FALSE)

;
COMMENT ON TABLE "rds"."obj_base" IS 'Abstract base obj';

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table obj_base
-- ----------------------------
ALTER TABLE "rds"."obj_base" ADD PRIMARY KEY ("obj_id");


DROP TABLE IF EXISTS "rds"."release_version";
CREATE TABLE "rds"."release_version" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"rv_version" varchar COLLATE "default" NOT NULL,
"rv_name" varchar COLLATE "default" NOT NULL
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table release_version
-- ----------------------------
ALTER TABLE "rds"."release_version" ADD PRIMARY KEY ("obj_id");

CREATE TABLE "rds"."project" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"project_name" varchar COLLATE "default" NOT NULL,
"project_build_version" int8 DEFAULT 1 NOT NULL,
"project_build_subversion" text NOT NULL,
"project_current_version" varchar(64) COLLATE "default",
"project_notification_email" varchar(64) COLLATE "default",
"project_notification_subject" varchar(64) COLLATE "default",
"script_migration_up" text COLLATE "default",
"script_migration_new" text COLLATE "default",
"script_config_local" text COLLATE "default",
"project_servers" text COLLATE "default",
"script_remove_release" text COLLATE "default",
"script_cron" text COLLATE "default",
"script_deploy" text COLLATE "default",
"script_build" text COLLATE "default",
"script_use" text COLLATE "default"
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table project
-- ----------------------------
ALTER TABLE "rds"."project" ADD PRIMARY KEY ("obj_id");


CREATE TABLE "rds"."release_request" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"rr_comment" varchar COLLATE "default" NOT NULL,
"rr_project_obj_id" int8 NOT NULL,
"rr_build_version" varchar COLLATE "default",
"rr_project_owner_code" varchar COLLATE "default",
"rr_release_engineer_code" varchar COLLATE "default",
"rr_project_owner_code_entered" bool DEFAULT false,
"rr_release_engineer_code_entered" bool DEFAULT false,
"rr_status" varchar COLLATE "default" DEFAULT 'new'::character varying,
"rr_old_version" varchar COLLATE "default",
"rr_use_text" text COLLATE "default",
"rr_last_time_on_prod" timestamptz(6),
"rr_revert_after_time" timestamptz(6),
"rr_release_version" int8,
"rr_new_migration_count" int8 DEFAULT 0,
"rr_migration_status" varchar COLLATE "default" DEFAULT 'none'::character varying,
"rr_new_migrations" text COLLATE "default",
"rr_built_time" timestamptz(6),
"rr_cron_config" text COLLATE "default",
"rr_post_migration_status" varchar COLLATE "default" DEFAULT 'none'::character varying,
"rr_new_post_migrations" text COLLATE "default",
"rr_migration_error" text COLLATE "default",
"rr_leading_id" int8,
"rr_build_started" timestamptz(6),
"rr_user_id" int8 NOT NULL
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Uniques structure for table release_request
-- ----------------------------
ALTER TABLE "rds"."release_request" ADD UNIQUE ("rr_project_obj_id", "rr_build_version");

-- ----------------------------
-- Primary Key structure for table release_request
-- ----------------------------
ALTER TABLE "rds"."release_request" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."release_request"
-- ----------------------------
ALTER TABLE "rds"."release_request" ADD FOREIGN KEY ("rr_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE "rds"."release_request" ADD FOREIGN KEY ("rr_leading_id") REFERENCES "rds"."release_request" ("obj_id") ON DELETE SET NULL ON UPDATE SET NULL;
ALTER TABLE "rds"."release_request" ADD FOREIGN KEY ("rr_user_id") REFERENCES "rds"."user" ("id") ON DELETE NO ACTION ON UPDATE NO ACTION;



CREATE TABLE "rds"."release_reject" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"rr_comment" varchar COLLATE "default" NOT NULL,
"rr_project_obj_id" int8 NOT NULL,
"rr_release_version" int8,
"rr_user_id" int8 NOT NULL
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table release_reject
-- ----------------------------
ALTER TABLE "rds"."release_reject" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."release_reject"
-- ----------------------------
ALTER TABLE "rds"."release_reject" ADD FOREIGN KEY ("rr_user_id") REFERENCES "rds"."user" ("id") ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE "rds"."release_reject" ADD FOREIGN KEY ("rr_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;




DROP TABLE IF EXISTS "rds"."project_config";
CREATE TABLE "rds"."project_config" (
"obj_id" bigserial NOT NULL,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"pc_project_obj_id" int8 NOT NULL,
"pc_filename" varchar(128) COLLATE "default" NOT NULL,
"pc_content" text COLLATE "default"
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Indexes structure for table project_config
-- ----------------------------
CREATE UNIQUE INDEX "rds_project_config_project_id_filename" ON "rds"."project_config" USING btree ("pc_project_obj_id", "pc_filename");

-- ----------------------------
-- Primary Key structure for table project_config
-- ----------------------------
ALTER TABLE "rds"."project_config" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."project_config"
-- ----------------------------
ALTER TABLE "rds"."project_config" ADD FOREIGN KEY ("pc_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;


CREATE TABLE "rds"."rds_db_config" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"red_lamp_wts_timeout" timestamptz(6),
"preprod_online" bool DEFAULT true NOT NULL,
"cpu_usage_last_truncate" timestamptz(6),
"is_tst_updating_enabled" int2 DEFAULT 1,
"red_lamp_team_city_timeout" timestamptz(6),
"red_lamp_wts_dev_timeout" timestamptz(6),
"crm_lamp_timeout" timestamptz(6),
"deployment_enabled" bool DEFAULT true,
"deployment_enabled_reason" text COLLATE "default"
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Records of rds_db_config
-- ----------------------------
INSERT INTO "rds"."rds_db_config" VALUES ('1', NOW(), NOW(), '1', '2016-09-22 11:54:28-04', 't', '2017-06-26 09:48:45-04', '1', '2016-09-22 11:54:34-04', '2016-09-22 11:54:36-04', '2016-09-22 11:54:29-04', 't', null);

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table rds_db_config
-- ----------------------------
ALTER TABLE "rds"."rds_db_config" ADD PRIMARY KEY ("obj_id");



CREATE TABLE "rds"."worker" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"worker_name" varchar COLLATE "default" NOT NULL
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

DROP TABLE IF EXISTS "rds"."project2worker";
CREATE TABLE "rds"."project2worker" (
"obj_id" bigserial NOT NULL,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"worker_obj_id" int8 NOT NULL,
"project_obj_id" int8 NOT NULL,
"p2w_current_version" varchar(64) COLLATE "default"
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table project2worker
-- ----------------------------
ALTER TABLE "rds"."project2worker" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."project2worker"
-- ----------------------------
ALTER TABLE "rds"."project2worker" ADD FOREIGN KEY ("worker_obj_id") REFERENCES "rds"."worker" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE "rds"."project2worker" ADD FOREIGN KEY ("project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;


-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table worker
-- ----------------------------
ALTER TABLE "rds"."worker" ADD PRIMARY KEY ("obj_id");



CREATE TABLE "rds"."project2project" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"parent_project_obj_id" int8 NOT NULL,
"child_project_obj_id" int8 NOT NULL
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Indexes structure for table project2project
-- ----------------------------
CREATE UNIQUE INDEX "rds_project2project_unique_parent_child" ON "rds"."project2project" USING btree ("parent_project_obj_id", "child_project_obj_id");

-- ----------------------------
-- Primary Key structure for table project2project
-- ----------------------------
ALTER TABLE "rds"."project2project" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."project2project"
-- ----------------------------
ALTER TABLE "rds"."project2project" ADD FOREIGN KEY ("child_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE CASCADE ON UPDATE NO ACTION;
ALTER TABLE "rds"."project2project" ADD FOREIGN KEY ("parent_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE CASCADE ON UPDATE NO ACTION;


CREATE TABLE "rds"."project_config_history" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"pch_project_obj_id" int8 NOT NULL,
"pch_config" text COLLATE "default",
"pch_filename" varchar(128) COLLATE "default" DEFAULT 'config.local.php'::character varying NOT NULL,
"pch_user_id" int8 NOT NULL
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table project_config_history
-- ----------------------------
ALTER TABLE "rds"."project_config_history" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."project_config_history"
-- ----------------------------
ALTER TABLE "rds"."project_config_history" ADD FOREIGN KEY ("pch_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE "rds"."project_config_history" ADD FOREIGN KEY ("pch_user_id") REFERENCES "rds"."user" ("id") ON DELETE NO ACTION ON UPDATE NO ACTION;


CREATE TABLE "rds"."log" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 12 NOT NULL,
"log_text" text COLLATE "default",
"log_user_id" int8
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table log
-- ----------------------------
ALTER TABLE "rds"."log" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."log"
-- ----------------------------
ALTER TABLE "rds"."log" ADD FOREIGN KEY ("log_user_id") REFERENCES "rds"."user" ("id") ON DELETE NO ACTION ON UPDATE NO ACTION;



CREATE TABLE "rds"."build" (
"obj_id" bigserial,
"obj_created" timestamptz(6) DEFAULT now() NOT NULL,
"obj_modified" timestamptz(6) DEFAULT now() NOT NULL,
"obj_status_did" int2 DEFAULT 1 NOT NULL,
"build_release_request_obj_id" int8,
"build_worker_obj_id" int8 NOT NULL,
"build_project_obj_id" int8 NOT NULL,
"build_status" varchar COLLATE "default" DEFAULT 'new'::character varying NOT NULL,
"build_attach" text COLLATE "default" DEFAULT ''::text,
"build_version" varchar(64) COLLATE "default" DEFAULT NULL::character varying,
"build_time_log" text COLLATE "default" DEFAULT '[]'::text
)
INHERITS ("rds"."obj_base") 
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table build
-- ----------------------------
ALTER TABLE "rds"."build" ADD PRIMARY KEY ("obj_id");

-- ----------------------------
-- Foreign Key structure for table "rds"."build"
-- ----------------------------
ALTER TABLE "rds"."build" ADD FOREIGN KEY ("build_release_request_obj_id") REFERENCES "rds"."release_request" ("obj_id") ON DELETE SET NULL ON UPDATE SET NULL;
ALTER TABLE "rds"."build" ADD FOREIGN KEY ("build_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE "rds"."build" ADD FOREIGN KEY ("build_worker_obj_id") REFERENCES "rds"."worker" ("obj_id") ON DELETE NO ACTION ON UPDATE NO ACTION;




ALTER TABLE "rds"."project_config_history"
DROP CONSTRAINT "project_config_history_pch_project_obj_id_fkey",
ADD CONSTRAINT "project_config_history_pch_project_obj_id_fkey" FOREIGN KEY ("pch_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE "rds"."project_config"
ADD CONSTRAINT "rds_project_config_project_id_foreigs_idx" FOREIGN KEY ("pc_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE "rds"."project2worker"
DROP CONSTRAINT "project2worker_project_obj_id_fkey",
ADD CONSTRAINT "project2worker_project_obj_id_fkey" FOREIGN KEY ("project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT "project2worker_worker_obj_id_fkey" FOREIGN KEY ("worker_obj_id") REFERENCES "rds"."worker" ("obj_id") ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE "rds"."release_request"
DROP CONSTRAINT "release_request_rr_project_obj_id_fkey",
ADD CONSTRAINT "release_request_rr_project_obj_id_fkey" FOREIGN KEY ("rr_project_obj_id") REFERENCES "rds"."project" ("obj_id") ON DELETE CASCADE ON UPDATE NO ACTION;


alter table rds.build owner to rds;
alter table rds.log owner to rds;
alter table rds.obj_base owner to rds;
alter table rds.profile owner to rds;
alter table rds.project owner to rds;
alter table rds.project2project owner to rds;
alter table rds.project2worker owner to rds;
alter table rds.project_config owner to rds;
alter table rds.project_config_history owner to rds;
alter table rds.rds_db_config owner to rds;
alter table rds.release_reject owner to rds;
alter table rds.release_request owner to rds;
alter table rds.release_version owner to rds;
alter table rds.session owner to rds;
alter table rds.user owner to rds;
alter table rds.worker owner to rds;

 alter sequence public.user_id_seq owner to rds;
 alter sequence rds.build_obj_id_seq owner to rds;
 alter sequence rds.log_obj_id_seq owner to rds;
 alter sequence rds.project2project_obj_id_seq owner to rds;
 alter sequence rds.project2worker_obj_id_seq owner to rds;
 alter sequence rds.project_config_history_obj_id_seq owner to rds;
 alter sequence rds.project_config_obj_id_seq owner to rds;
 alter sequence rds.project_obj_id_seq owner to rds;
 alter sequence rds.rds_db_config_obj_id_seq owner to rds;
 alter sequence rds.release_reject_obj_id_seq owner to rds;
 alter sequence rds.release_request_obj_id_seq owner to rds;
 alter sequence rds.release_version_obj_id_seq owner to rds;
 alter sequence rds.worker_obj_id_seq owner to rds;

COPY "user" (id, username, email, password_hash, auth_key, confirmed_at, unconfirmed_email, blocked_at, registration_ip, created_at, updated_at, flags, last_login_at, phone) FROM stdin;
1	rds	rds@whotrades.org	$2y$10$lt0EyF3ncaolvd2zH7dpLevLf6Y2D1wOxNQmBZaW2U4l0rAWzp9g.	sowjrEIgzpBNcuX7pkvkm7-Et2g2aK_X	1510211975	\N	\N	\N	1510211975	1510211975	0	\N	\N
\.
