<?php
$this->serviceRds['jira']['createTags'] = false;
$this->serviceRds['jira']['tagTickets'] = false;
$this->serviceRds['jira']['transitionTickets'] = false;
$this->serviceRds['jira']['checkTicketStatus'] = false;
$this->environment = 'main';
$this->installToPreprod = true;

$this->hardMigration = [
    'autoStartEnvironments' => ['preprod'],
];
