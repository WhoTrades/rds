<?php /** @var $projects string[] */ ?>

<h2>Помощь в выкладке закрытых задач</h2>
<ul>
    <?foreach ($projects as $project) {?>
        <li><a href="<?=$this->createUrl('jira/ticketHide', ['project' => $project])?>"><?=$project?></a></li>
    <?}?>
</ul>

<h2>Незакрытые версии</h2>
<ul>
    <?foreach ($projects as $project) {?>
        <li>
            <b><?=$project?></b>
            <a href="<?=$this->createUrl('jira/versions', ['project' => $project])?>">Все</a>
            <a href="<?=$this->createUrl('jira/versions', ['project' => $project, 'released' => true])?>">Выпущенные</a>
            <a href="<?=$this->createUrl('jira/versions', ['project' => $project, 'released' => false])?>">Не выпущенныя</a>
        </li>
    <?}?>
</ul>
