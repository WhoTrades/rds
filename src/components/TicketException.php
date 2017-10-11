<?php
namespace whotrades\rds\components;

/**
 * Custom exception to catch while handle tickets by JIRA API
 *
 * @author Vassiliy Savunov
 */
class TicketException extends \yii\base\Exception
{
    /**
     * TicketException constructor.
     * {@inheritdoc}
     */
    public function __construct($message, $code = null)
    {
        parent::__construct($message, $code ?: 0);
    }
}
