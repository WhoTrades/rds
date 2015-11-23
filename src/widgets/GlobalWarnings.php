<?php
class GlobalWarnings extends CWidget
{
    public function init()
    {
        if (Yii::app()->user->isGuest) {
            return;
        }

        $warnings = [];
        $config = RdsDbConfig::get();

        if (!$config->is_tst_updating_enabled) {
            $warnings[] = new GlobalWarningItem(
                "Обновление tst контура остановлено",
                TbHtml::ICON_WARNING_SIGN,
                TbHtml::ALERT_COLOR_WARNING
            );
        }

        $this->render('application.views.widgets.GlobalWarnings', [
            'warnings' => $warnings,
        ]);
    }
}

class GlobalWarningItem {
    public $message;
    public $icon;
    public $color;

    public function __construct($message, $icon, $color)
    {
        $this->message  = $message;
        $this->icon     = $icon;
        $this->color    = $color;
    }
}