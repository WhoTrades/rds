<?/* @var $this PostMigration*/?>
<?/* @var $releaseRequests ReleaseRequest[]*/?>
<?if ($releaseRequests) {?>
    <div style="float: right; margin: 20px;">
        <h4>POST миграции</h4>
        <?foreach($releaseRequests as $rr) {?>
            <?if (!$rr->rr_new_post_migrations || !json_encode($rr->rr_new_post_migrations)) { continue;} ?>
            <h5 style="float: left; margin: 0 20px 0 0"><?=$rr->project->project_name?> :: <?=$rr->rr_build_version?> (<?=count(json_decode($rr->rr_new_post_migrations))?>)</h5>
            <?if ($rr->rr_post_migration_status == \ReleaseRequest::MIGRATION_STATUS_UPDATING) {?>
                Updating migrations
            <?} elseif ($rr->rr_post_migration_status == \ReleaseRequest::MIGRATION_STATUS_FAILED) {?>
                Migrations failed
            <?} else {?>
                <a href="/use/migrate/?id=<?=$rr->obj_id?>&type=post">Накатить</a>
                <div style="clear: both"></div>
                <?foreach (json_decode($rr->rr_new_post_migrations) as $migration) {?>
                    <a href='http://sources:8060/browse/migration-<?=$rr->project->project_name?>/post/<?=$migration?>.php?hb=true'><?=$migration ?></a><br />
                <?}?>
            <?}?>
        <?}?>
    </div>
<?}?>
