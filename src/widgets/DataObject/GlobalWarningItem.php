<?php
namespace whotrades\rds\widgets\DataObject;

class GlobalWarningItem
{
    public $message;
    public $icon;
    public $color;

    /**
     * GlobalWarningItem constructor.
     * @param string $message
     * @param string $icon
     * @param string $color
     */
    public function __construct($message, $icon, $color)
    {
        $this->message  = $message;
        $this->icon     = $icon;
        $this->color    = $color;
    }
}
