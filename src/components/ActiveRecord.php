<?php
namespace whotrades\rds\components;

class ActiveRecord extends \yii\db\ActiveRecord
{
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
            $transaction->rollBack();
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

    public static function updateByPk($id, $attributes)
    {
        return self::updateAll($attributes, ['obj_id' => $id]);
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
