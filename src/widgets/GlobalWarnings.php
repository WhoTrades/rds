<?php
namespace whotrades\rds\widgets;

use whotrades\rds\widgets\DataObject\GlobalWarningItem;

class GlobalWarnings extends \yii\base\Widget
{
    /**
     * @return string
     */
    public function run()
    {
        if (\Yii::$app->user->isGuest) {
            return "";
        }

        $warnings = [];
        $config = \whotrades\rds\models\RdsDbConfig::get();

        if (!$config->is_tst_updating_enabled) {
            $warnings[] = new GlobalWarningItem(
                "Обновление tst контура остановлено",
                'warning-sign',
                'warning'
            );
        }

        return $this->render('@app/views/widgets/GlobalWarnings', [
            'warnings' => $warnings,
        ]);
    }
}
