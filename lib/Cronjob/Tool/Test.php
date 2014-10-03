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
        $statuses = ['installed', 'used', 'using', 'used_attempt'];

        $id = 281;

        $rr = ReleaseRequest::model()->updateByPk($id, ['rr_status' => $statuses[array_rand($statuses)]]);

        Yii::app()->assetManager->setBasePath(dirname(Yii::getPathOfAlias('application'))."/main/www/assets");
        Yii::app()->assetManager->setBaseUrl('/assets');
        Yii::app()->urlManager->setBaseUrl('');
        $filename = Yii::getPathOfAlias('application.views.site._releaseRequestRow').'.php';
        $rowTemplate = include($filename);

        list($controller, $action) = Yii::app()->createController('/');
        $controller->setAction($controller->createAction($action));
        Yii::app()->setController($controller);
        $rr = ReleaseRequest::model();
        $rr->obj_id = $id;
        $widget = Yii::app()->getWidgetFactory()->createWidget(Yii::app(),'bootstrap.widgets.TbGridView', [
            'dataProvider'=>new CActiveDataProvider($rr, $rr->search()),
            'columns'=>$rowTemplate,
            'rowCssClassExpression' => function(){return 'rowItem';},
        ]);
        $widget->init();
        ob_start();
        $widget->run();
        $html = ob_get_clean();

        $comet = Yii::app()->realplexor;
        $comet->send('releaseRequestChanged', ['rr_id' => $id, 'html' => $html]);
        $this->debugLogger->message("Sended");
    }

    public function createUrl($route, $params)
    {
        /** @var $yii WebApplication */
        $yii = Yii::app();
        return $yii->createUrl($route, $params);
    }
}
