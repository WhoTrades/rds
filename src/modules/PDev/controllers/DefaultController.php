<?php
/**
 * Контроллер для отображения страниц управления персональным контуром (переключение ветки, перекборка и тому подобное)
 *
 * @author Artem Naumenko
 */
class DefaultController extends Controller
{
    // an: Папка, где мы ищем установленный проект
    const DEFAULT_PATH = "/home/dev/dev/";

    // an: Папка, где GitUpdater хранит задачи для переключения
    // @see \GitUpdater::COMMANDS_DIR
    const UPDATER_TASKS_DIR = "/var/tmp/updater/";

    public $pageTitle = 'Управление персональным контуром';

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array('allow',  // allow all users to perform 'index' and 'view' actions
                'users' => array('@'),
            ),
            array('deny',  // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Отображает основную панель управления
     * @return void
     */
    public function actionIndex()
    {
        $basePath = self::DEFAULT_PATH . ".git/refs/heads/";

        if (!empty($_POST['action']) && $_POST['action'] == 'switch') {
            $string = $_POST['branch'];
            if (preg_match('~https?://[\w.]+/browse/(\w+-\d+)/?$~', $string, $ans)) {
                $branch = "feature/" . $ans[1];
            } else {
                $branch = $string;
            }

            // an: Отправляем задачу в Rabbit на переключение ветки
            (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel()->sendPDevSwitchBranch(
                new \RdsSystem\Message\PDev\SwitchBranch(self::DEFAULT_PATH, $branch)
            );

            $this->render('switching');

            return;
        }

        $tasks = glob(self::UPDATER_TASKS_DIR . "*");

        $currentBranch = file_get_contents(self::DEFAULT_PATH . ".git/HEAD");
        $currentBranch = trim(str_replace("ref: refs/heads/", "", $currentBranch));
        $currentBranch = str_replace($basePath, "", $currentBranch);

        $this->render('index', [
            'currentBranch'     => $currentBranch,
            'tasks'             => $tasks,
        ]);
    }
}
