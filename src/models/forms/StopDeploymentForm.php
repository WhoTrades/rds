<?php
namespace whotrades\rds\models\forms;

class StopDeploymentForm extends \yii\base\Model
{
    public $reason;
    public $status;

    /**
     * Returns the list of attribute names of the model.
     * @return array list of attribute names.
     */
    public function attributeLabels()
    {
        return [
            'reason' => \Yii::t('rds', 'shutdown_reason'),
            'status' => 'Статус',
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['status'], 'required'],
            ['reason', 'check', 'skipOnEmpty' => false],
        ];
    }

    /**
     * @return void
     */
    public function check()
    {
        if (! $this->status && ! $this->reason) {
            $this->addError('reason', \Yii::t('rds/errors', 'prod_deploy_disabled_reason_required'));
        }
    }

    /**
     * Returns the list of attribute names of the model.
     * @return array list of attribute names.
     */
    public function attributeNames()
    {
        return array_keys($this->attributeLabels());
    }
}
