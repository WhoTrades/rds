<table class="items table table-hover">
    <thead>
    <tr>
        <th>Коммит</th>
        <th>Автор</th>
        <th>Комментарий</th>
    </thead>
    <tbody>
        <?$repo = null?>
        <?foreach ($commits as $key => $commit) {?>
            <?/** @var $commits JiraCommit */?>
            <?if ($repo != $commit->jira_commit_repository) {?>
                <tr class="odd">
                    <td colspan="3">
                        <h3><a href="http://git.finam.ru/projects/WT/repos/<?=$commit->jira_commit_repository?>/commits"><?=$commit->jira_commit_repository?></a></h3>
                    </td>
                </tr>
                <?$repo = $commit->jira_commit_repository?>
            <?}?>
            <tr class="<?=$key%2 ? 'odd' : 'even'?>">
                <td>
                    <?if ($commit->jira_commit_repository) {?>
                        <a href="http://git.finam.ru/projects/WT/repos/<?=$commit->jira_commit_repository?>/commits/<?=$commit->jira_commit_hash?>">
                            <?=substr($commit->jira_commit_hash, 0, 8)?>
                        </a>
                    <?} else {?>
                        <?=substr($commit->jira_commit_hash, 0, 8)?>
                    <?}?>
                </td>
                <td><?=$commit->jira_commit_author?></td>
                <td><?=$commit->jira_commit_comment?></td>
            </tr>
        <?}?>
    </tbody>
</table>

