<?php use whotrades\rds\modules\Wtflow\models\JiraCommit;?>
<table class="items table table-hover">
    <thead>
    <tr>
        <th>Коммит</th>
        <th>Автор</th>
        <th>Комментарий</th>
    </thead>
    <tbody>
    <?php $repo = null ?>
    <?php foreach ($commits as $key => $commit) { ?>
        <?php /** @var $commits JiraCommit */ ?>
        <?php if ($repo != $commit->jira_commit_repository) { ?>
            <tr class="odd">
                <td colspan="3">
                    <h3>
                        <a href="http://git.finam.ru/projects/WT/repos/<?= $commit->jira_commit_repository ?>/commits"><?= $commit->jira_commit_repository ?></a>
                    </h3>
                </td>
            </tr>
            <?php $repo = $commit->jira_commit_repository ?>
        <?php } ?>
        <tr class="<?= $key % 2 ? 'odd' : 'even' ?>">
            <td>
                <?php if ($commit->jira_commit_repository) { ?>
                    <a href="http://git.finam.ru/projects/WT/repos/<?= $commit->jira_commit_repository ?>/commits/<?= $commit->jira_commit_hash ?>">
                        <?= substr($commit->jira_commit_hash, 0, 8) ?>
                    </a>
                <?php } else { ?>
                    <?= substr($commit->jira_commit_hash, 0, 8) ?>
                <?php } ?>
            </td>
            <td><?= $commit->jira_commit_author ?></td>
            <td><?= $commit->jira_commit_comment ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>

