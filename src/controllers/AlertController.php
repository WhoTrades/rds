<?php
namespace app\controllers;

use app\models\RdsDbConfig;
use app\models\AlertLog;

class AlertController extends Controller
{
    const TIMEZONE = "Europe/Moscow";

    public $pageTitle = 'Сигнализация';

    const ALERT_LIST_TYPE_ERRORS = 'errors';
    const ALERT_LIST_TYPE_IGNORES = 'ignores';

    /**
     * @throws \Exception
     */
    public function actionIndex()
    {
        if (!empty($_POST['disable'])) {
            foreach ($_POST['disable'] as $id => $timeout) {
                $lamp = Lamp::model()->findByPk($id);
                if (!$lamp) {
                    continue;
                }
                $lamp->lamp_timeout = date("Y-m-d H:i:s", strtotime($timeout));
                $lamp->save();
            }
            $this->redirect("/alert/");
        }

        if (!empty($_POST['ignore'])) {
            foreach ($_POST['ignore'] as $id => $time) {
                $alertLog = AlertLog::findByPk($id);

                if ($alertLog) {
                    $alertLog->alert_ignore_timeout = date(DATE_ISO8601, strtotime($time));
                    $alertLog->save(false);
                }
            }
            $this->redirect("/alert/");
        }

        if (!empty($_POST['add_receiver'])) {
            foreach ($_POST['add_receiver'] as $id => $phone) {
                $lamp = Lamp::model()->findByPk($id);
                if (!$lamp) {
                    continue;
                }
                $lamp->addReceiver($phone);
                $lamp->save();
            }
            $this->redirect("/alert/");
        }

        if (!empty($_POST['remove_receiver'])) {
            foreach ($_POST['remove_receiver'] as $id => $phone) {
                $lamp = Lamp::model()->findByPk($id);
                if (!$lamp) {
                    continue;
                }
                $lamp->removeReceiver($phone);
                $lamp->save();
            }
            $this->redirect("/alert/");
        }

        $c = new CDbCriteria();
        $c->order = 'obj_id asc';
        $lamps = Lamp::model()->findAll($c);

        $this->render('index', [
            'lamps' => $lamps,
        ]);
    }

    /**
     *
     */
    public function actionGetAllLampStatusJson()
    {
        $result = [];

        foreach (Lamp::model()->findAll() as $lamp) {
            /** @var $lamp Lamp*/
            $result[$lamp->lamp_name] = [
                'alert' => $lamp->getLampStatus(),
            ];
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * @param string $lampName
     */
    public function actionGetLampStatusJson($lampName)
    {
        $lamp = Lamp::model()->findByLampName($lampName);
        $result = ['alert' => $lamp->getLampStatus()];

        echo json_encode($result, JSON_PRETTY_PRINT);
    }
}
