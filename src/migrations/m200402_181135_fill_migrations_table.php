<?php

use \whotrades\rds\migrations\base;
use whotrades\rds\models\PostMigration;
use whotrades\rds\models\Migration;
use whotrades\rds\models\ReleaseRequest;

/**
 * Class m200402_181135_fill_migrations_table
 */
class m200402_181135_fill_migrations_table extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $postMigrationList = PostMigration::find()->all();

        /** @var PostMigration $postMigration */
        foreach ($postMigrationList as $postMigration) {
            if (!Migration::findByAttributes(['migration_name' => $postMigration->pm_name, 'migration_project_obj_id' => $postMigration->pm_project_obj_id])) {
                $migration = new Migration();

                $migration->obj_created = $postMigration->obj_created;
                $migration->obj_modified = $postMigration->obj_modified;
                switch ($postMigration->pm_status) {
                    case PostMigration::STATUS_FAILED:
                        $migration->obj_status_did = Migration::STATUS_FAILED_APPLICATION;
                        break;
                    default:
                        $migration->obj_status_did = $postMigration->pm_status;
                }
                $migration->migration_name = $postMigration->pm_name;
                $migration->migration_type = Migration::TYPE_ID_POST;
                $migration->migration_project_obj_id = $postMigration->pm_project_obj_id;
                $migration->migration_release_request_obj_id = $postMigration->pm_release_request_obj_id;
                $migration->migration_log = $postMigration->pm_log;
                $migration->save();
                $migration->fillFromGit();
            }
        }

        $releaseRequestList = ReleaseRequest::find()->where(['IS NOT', 'rr_new_migrations', null])->orderBy(['obj_created' => SORT_ASC])->all();

        /** @var ReleaseRequest $releaseRequest */
        foreach ($releaseRequestList as $releaseRequest) {
            foreach (json_decode($releaseRequest->rr_new_migrations) as $preMigrationName) {
                if (!Migration::findByAttributes(['migration_name' => $preMigrationName, 'migration_project_obj_id' => $releaseRequest->project->obj_id])) {
                    $migration = new Migration();

                    $migration->obj_created = $releaseRequest->obj_created;
                    $migration->obj_modified = $releaseRequest->obj_created;
                    $migration->obj_status_did = Migration::STATUS_APPLIED;
                    $migration->migration_name = $preMigrationName;
                    $migration->migration_type = Migration::TYPE_ID_PRE;
                    $migration->migration_project_obj_id = $releaseRequest->project->obj_id;
                    $migration->migration_release_request_obj_id = $releaseRequest->obj_id;
                    $migration->migration_log = $releaseRequest->rr_migration_error;
                    $migration->save();
                    $migration->fillFromGit();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('TRUNCATE TABLE rds.migration');
    }
}
