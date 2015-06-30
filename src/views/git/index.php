<h1>Ветки, которые нельзя удалить из-за неслитых коммитов</h1>
<?foreach ($list as $val) {?>
    <div>
        <h2>Ветка "<?=$val['jf_branch']?>"</h2>
        <?foreach($val['jf_blocker_commits'] as $repo => $commits) {?>
            <h3>Репозиторий "<?=$repo?>"</h3>
            <pre><?=preg_replace('~[a-f0-9]{40}~', '<a href="http://stash/projects/WT/repos/'.$repo.'/commits/$0">$0</a>', $commits)?></pre>
        <?}?>
    </div>
<?}?>
