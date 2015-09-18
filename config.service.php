<?php
//an: Так как тестового гита, жиры, тимсити и т.д. у нас нет - то все интеграционные штуки по умолчанию отключаем.
// Включаем только на время отладки в config.local во время отладки и в config.local на прод контуре

$this->serviceRds['jira']['createTags'] = false;
$this->serviceRds['jira']['tagTickets'] = false;
$this->serviceRds['jira']['transitionTickets'] = false;
$this->serviceRds['jira']['checkTicketStatus'] = false;
$this->serviceRds['jira']['mergeTasks'] = false;
$this->serviceRds['stash']['createPullRequest'] = false;
$this->environment = 'main';
$this->serviceRds['alerts']['lampOnEmail'] = 'oops+lamp-on@whotrades.org';
$this->serviceRds['alerts']['lampOffEmail'] = 'oops+lamp-off@whotrades.org';

