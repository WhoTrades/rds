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
                        <h3><a href="http://sources:8060/changelog/<?=$commit->jira_commit_repository?>"><?=$commit->jira_commit_repository?></a></h3>
                    </td>
                </tr>
                <?$repo = $commit->jira_commit_repository?>
            <?}?>
            <tr class="<?=$key%2 ? 'odd' : 'even'?>">
                <td>
                    <a href="http://sources:8060/changelog/<?=$commit->jira_commit_repository?>?cs=<?=$commit->jira_commit_hash?>">
                        <?=substr($commit->jira_commit_hash, 0, 8)?>
                    </a>
                </td>
                <td><?=$commit->jira_commit_author?></td>
                <td><?=$commit->jira_commit_comment?></td>
            </tr>
        <?}?>
    </tbody>
</table>

