<?php
class ActiveRecord extends CActiveRecord
{
    public function afterConstruct() {
        if ($this->isNewRecord) {
            $this->obj_created = date("r");
            $this->obj_modified = date("r");
        }
        return parent::afterConstruct();
    }

    public function save($runValidation=true, $attributes=null)
    {
        if ($this->getDbConnection()->getCurrentTransaction()) {
            return parent::save($runValidation, $attributes);
        }

        $transaction = $this->getDbConnection()->beginTransaction();

        try {
            $result = parent::save($runValidation, $attributes);
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }

        $transaction->commit();

        return $result;
    }
}