<?php
class SingleLoginUser extends CUserIdentity
{
    private $id, $email;

    public function __construct($id, $email)
    {
        $this->id = $id;
        $this->username = $email;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
