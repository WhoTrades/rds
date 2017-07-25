<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test_ApplicationInstallation -vv
 */

use ApplicationRequirementsSystem\Checker\Container;
use ApplicationRequirementsSystem\Checker\DirectoryAccessible;
use ApplicationRequirementsSystem\Checker\FileAccessible;
use ApplicationRequirementsSystem\Checker\UrlAcceptable;
use CompanyInfrastructure\BitBacketApi;
use RdsSystem\Model\Rabbit\MessagingRdsMs;

class Cronjob_Tool_Test_ApplicationInstallation extends Cronjob\Tool\ToolBase
{
    /**
     * Use this function to get command line spec for cronjob
     *
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [];
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     *
     * @return int
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $checkerSystem = new ApplicationRequirementsSystem\Factory();
        $container = $checkerSystem->createCheckerContainer();

        $this->addRdsCheckers($container);

        return $checkerSystem->runCheckContainerCheckers($container);
    }

    private function addRdsCheckers(Container $container)
    {
        $container->addChecker(new FileAccessible(dirname(dirname(dirname(dirname(__DIR__)))) . '/config.local.php', true, true));
        $container->addChecker(new DirectoryAccessible(Yii::$app->runtimePath, true, true, true));

        foreach (Yii::$app->webSockets->zmqLocations as $url) {
            $container->addChecker(new UrlAcceptable($url, 0.1));
        }

        $container->addChecker(new UrlAcceptable(
            Yii::$app->graphite->protocol . "://" . \Yii::$app->graphite->host . ":" . \Yii::$app->graphite->port
        ));

        $container->addChecker(new UrlAcceptable(Yii::$app->modules['SingleLogin']['components']['auth']['crmUrl']), 'CRM URL');
        $container->addChecker(new UrlAcceptable(Yii::$app->params['jira']['baseRdsJiraUrl']), 'JIRA URL');
        $container->addChecker(new UrlAcceptable(BitBacketApi::DEFAULT_STASH_URL), 'Stash/Bitbucket URL');
        $container->addChecker(new UrlAcceptable('tcp://' . MessagingRdsMs::HOST . ":" . MessagingRdsMs::PORT));
    }
}
