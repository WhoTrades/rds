<?php
namespace app\components;

class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        if ($this->isNewRecord) {
            $this->obj_created = date("r");
            $this->obj_modified = date("r");
        }
        parent::__construct($config);
    }

    /**
     * @param bool $runValidation
     * @param null $attributes
     *
     * @return bool
     * @throws \Exception
     */
    public function save($runValidation = true, $attributes = null)
    {
        if ($this->getDbConnection()->getTransaction()) {
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

    /**
     * @param int $id
     *
     * @return static
     */
    public static function findByPk($id)
    {
        return static::findOne(['obj_id' => $id]);
    }

    /**
     * @param array $condition
     *
     * @return static
     */
    public static function findByAttributes($condition)
    {
        return static::find()->where($condition)->one();
    }

    /**
     * @param array $condition
     *
     * @return int
     */
    public static function countByAttributes($condition)
    {
        return static::find()->where($condition)->count();
    }

    /**
     * @param array $condition
     *
     * @return static[]
     */
    public static function findAllByAttributes($condition)
    {
        return static::find()->where($condition)->all();
    }

    /**
     * Added for campatibility with yii 1.1
     * @return \yii\db\Connection
     */
    public function getDbConnection()
    {
        return static::getDb();
    }
}
