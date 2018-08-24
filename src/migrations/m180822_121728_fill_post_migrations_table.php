<?php

use yii\db\Migration;

/**
 * Class m180822_121728_fill_post_migrations_table
 */
class m180822_121728_fill_post_migrations_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $releaseRequestList = \whotrades\rds\models\ReleaseRequest::find()
            ->where(['IS NOT', 'rr_new_post_migrations', null])
            ->andWhere("obj_created > '" . date('Y-m-d', strtotime('- 10 days')) . "'")
            ->orderBy(['obj_created' => SORT_ASC])
            ->all();

        /** @var \whotrades\rds\models\ReleaseRequest $releaseRequest */
        foreach ($releaseRequestList as $releaseRequest) {
            foreach (json_decode($releaseRequest->rr_new_post_migrations) as $postMigrationName) {
                $postMigrationName = str_replace('/', '\\', $postMigrationName);

                if (!\whotrades\rds\models\PostMigration::findByAttributes(['pm_name' => $postMigrationName, 'pm_project_obj_id' => $releaseRequest->project->obj_id])) {
                    $postMigration = new \whotrades\rds\models\PostMigration();

                    $postMigration->pm_name = $postMigrationName;
                    $postMigration->pm_project_obj_id = $releaseRequest->project->obj_id;
                    $postMigration->pm_release_request_obj_id = $releaseRequest->obj_id;
                    $postMigration->obj_created = $releaseRequest->obj_created;
                    $postMigration->save();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('TRUNCATE TABLE rds.post_migration');
    }
}
