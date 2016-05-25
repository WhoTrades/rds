<?php /** @var $this PostMigration*/ ?>
<?php /** @var $releaseRequests ReleaseRequest[]*/ ?>
<?php if ($releaseRequests) {?>
    <div style="float: right; ">
        <h4>POST миграции</h4>
        <?php foreach ($releaseRequests as $rr) {?>
            <?php if (!$rr->rr_new_post_migrations || !json_encode($rr->rr_new_post_migrations)) {?>
                <?php continue; ?>
            <?php }?>
            <h5 style="float: left; margin: 0 20px 0 0"><?=$rr->project->project_name?> :: <?=$rr->rr_build_version?> (<?=count(json_decode($rr->rr_new_post_migrations))?>)</h5>
            <?php if ($rr->rr_post_migration_status == \ReleaseRequest::MIGRATION_STATUS_UPDATING) {?>
                Updating migrations
            <?php } elseif ($rr->rr_post_migration_status == \ReleaseRequest::MIGRATION_STATUS_FAILED) {?>
                Migrations failed
            <?php } elseif ($rr->rr_post_migration_status == \ReleaseRequest::MIGRATION_STATUS_NONE) {?>
                <a href="/use/migrate/?id=<?=$rr->obj_id?>&type=post">Накатить</a>
                <div style="clear: both"></div>
                <?php foreach (json_decode($rr->rr_new_post_migrations) as $migration) {?>
                    <a href='<?=$rr->project->getMigrationUrl($migration, 'post')?>'>
                        <?=$migration?>
                    </a><br />
                <?php }?>
            <?php }?>
        <?php }?>
    </div>
<?php }
