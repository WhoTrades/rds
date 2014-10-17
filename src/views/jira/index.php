<?php /** @var $projects string[] */ ?>

<h2>Помощь в выкладке закрытых задач</h2>
<ul>
    <?foreach ($projects as $project) {?>
        <li><a href="<?=$this->createUrl('jira/ticketHide', ['project' => $project])?>"><?=$project?></a></li>
    <?}?>
</ul>
