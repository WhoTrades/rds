<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */
class Cronjob_Tool_Test extends Cronjob\Tool\ToolBase
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return array(
            'action' => [
                'desc' => '',
                'useForBaseName' => true,
                'valueRequired' => true,
            ],
        );
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
//        $comet = Yii::app()->realplexor;
//        $comet->send('progressbar_change', ['rr_id' => 118, 'point' => 'git pull comon', 'progress' => '18.12']);

        $id = 276;
        Yii::app()->assetManager->setBasePath('/tmp');
        $row = Yii::getPathOfAlias('application.views.site._releaseRequestRow.php');
        $rr = ReleaseRequest::model()->findByPk($id);
        $widget = Yii::app()->getWidgetFactory()->createWidget(Yii::app(),'bootstrap.widgets.TbGridView', [
            'dataProvider'=>new CActiveDataProvider(ReleaseRequest::model(), ['id' => $id]),
            'columns'=>include($row),
        ]);
        $widget->init();
        echo $widget->run();
        echo 2;
    }
}
