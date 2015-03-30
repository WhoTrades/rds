<?php

/**
 * Custom exception to catch while handle tickets by JIRA API
 *
 * @author Vassiliy Savunov
 */
class TicketException extends CException {

    function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }
}