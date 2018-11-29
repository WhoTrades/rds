<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */

namespace whotrades\rds\controllers;

use Yii;
use yii\base\InlineAction;
use yii\web\Controller as ControllerBase;

class Controller extends ControllerBase
{
    // ag: Disable debugModule for API controllers
    protected $disableDebugModule = false;

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if ($this->disableDebugModule) {
            unset(Yii::$app->log->targets['debug']);
        }

        return parent::beforeAction($action);
    }

    /**
     * @var string the default layout for the controller view. Defaults to '//layouts/column1',
     * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
     */
    //public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }

        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Yii::createObject($actionMap[$id], [$id, $this]);
        } elseif (preg_match('/^[a-z0-9\\-_]+$/i', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && strtolower($method->getName()) == strtolower($methodName)) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }
}
