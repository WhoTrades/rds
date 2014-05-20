<?php
/**
 * @author Artem Naumenko
 * Класс, реализующий авторизацию через crm
 */
final class SingleLoginAuth extends \CComponent
{
    public $secretKey;
    public $crmUrl = 'http://crm.whotrades.com/';
    public $returnRoute = '/SingleLogin/auth/login';
    public $clientId = null;
    public $timeout = 10;

    public function getAuthUrl()
    {
        $hash = md5(uniqid().microtime()."dfgldfgkidfg98789h4i".rand(1, PHP_INT_MAX));
        $token = sha1($hash.'+'.$this->secretKey);
        $url = $this->crmUrl . "singlelogin/?" . http_build_query(array(
            'client_id' => $this->clientId,
            'hash' => $hash,
            'token' => $token,
            'return_url' => $this->getReturnUrl(),
        ));
        return $url;
    }

    public function init()
    {

    }

    public function authorize($code)
    {
        $url = $this->crmUrl.'/json-rpc.php';
        $token = sha1($code . '+' . $this->secretKey);
        $client = new JsonRpcClient($url, Yii::app()->debugLogger, null, $this->timeout);
        $result = $client->singleLoginConfirm($this->clientId, $code, $token);

        if ($result['status'] == 'ERROR') {
            return null;
        }

        $profile = $client->getClientByEmail($result['userEmail']);
        $phone = preg_replace('~^7~', '8', preg_replace('~\D~', '', $profile['phone']));

        \Yii::app()->user->setState('phone', $phone);
        \Yii::app()->session['userRights'] = $result['userRights'];

        return new SingleLoginUser($result['userId'], $result['userEmail']);
    }

    private function getReturnUrl()
    {
        return Yii::app()->controller->createAbsoluteUrl($this->returnRoute);
    }
}

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

