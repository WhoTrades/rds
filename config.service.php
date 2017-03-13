<?php
//an: Так как тестового гита, жиры, тимсити и т.д. у нас нет - то все интеграционные штуки по умолчанию отключаем.
// Включаем только на время отладки в config.local во время отладки и в config.local на прод контуре

$this->serviceRds['jira']['createTags'] = false;
$this->serviceRds['jira']['tagTickets'] = false;
$this->serviceRds['jira']['transitionTickets'] = false;
$this->serviceRds['jira']['checkTicketStatus'] = false;
$this->serviceRds['jira']['mergeTasks'] = false;
$this->serviceRds['jira']['asyncRpc'] = false;
$this->serviceRds['stash']['createPullRequest'] = false;
$this->environment = 'main';
$this->serviceRds['alerts']['lampFromEmail'] = 'rds-lamp@whotrades.org';
$this->serviceRds['alerts']['lampOnEmail'] = 'oops+lamp-on@whotrades.org';
$this->serviceRds['alerts']['lampOffEmail'] = 'oops+lamp-off@whotrades.org';
$this->serviceRds['alerts']['dataProvider'] = [
    'Monitoring' => [
        'enable' => true,
        'url' => 'https://monitoring.whotrades.net/?json=1',
    ],
    'MonitoringDEV' => [
        'enable' => true,
        'url' => 'http://monitoring.dev.whotrades.net/?json=1',
    ],
    'MonitoringTST' => [
        'enable' => true,
        'url' => 'http://monitoring.tst.whotrades.net/?json=1',
    ],
];

$this->serviceRds['jira']['codeReview'] = [
    'WTT' => [
        '*' => 40,
    ],
    'WTS' => [
        '*' => 40,
    ],
    'WTA' => [
        '*' => 50,
    ],
    'WTI' => [
        '*' => 40,
    ],
];

$this->graphiteSystem = array(
    'host'     => 'graphite.local',
    'port'     => 8125,
    'protocol' => 'udp',
    'env'      => 'prod',
    'prefix'   => 'rds',
);
