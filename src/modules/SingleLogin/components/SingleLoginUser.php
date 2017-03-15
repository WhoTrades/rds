<?php
namespace app\modules\SingleLogin\components;

use yii\web\IdentityInterface;

class SingleLoginUser implements IdentityInterface
{
    private $id;
    private $email;
    private $data;

    /**
     * @param int $id
     * @param string $email
     */
    public function __construct($id, $email)
    {
        $this->id = $id;
        $this->username = $email;
    }

    public function getPhone()
    {
        return $this->data['phone'] ?? null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param int $id
     *
     * @throws \NotImplementedException
     * @return void
     */
    public static function findIdentity($id)
    {
        throw new \NotImplementedException();
    }

    /**
     * @param string $token
     * @param null  $type
     * @return void
     *
     * @throws \NotImplementedException
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new \NotImplementedException();
    }

    /**
     * @return string
     */
    public function getAuthKey()
    {
        return '';
    }

    /**
     * @param string $authKey
     * @return bool
     */
    public function validateAuthKey($authKey)
    {
        return true;
    }

    /**
     * @param array $data
     */
    public function setPersistentStates(array $data)
    {
        $this->data = $data;
    }
}
