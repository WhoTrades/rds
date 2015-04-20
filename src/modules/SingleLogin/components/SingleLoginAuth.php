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

    /**
     * CRM Rpc Client Auth credentials
     * @author Anton Zhukov
     * @var array
     */
    public $token = 'service-rds';
    public $secret = 'w72q742oa3O11s1v0OqxdziJKV48CDr0';

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
        $token = sha1($code . '+' . $this->secretKey);

        $crmRpcConfig = [
            'url' => $this->crmUrl . 'json-rpc.php',
            'token' => $this->token,
            'secret' => $this->secret,
        ];

        $client = \CrmSystem\Factory::createRpcClient(
            $crmRpcConfig,
            Yii::app()->debugLogger,
            null,
            $this->timeout
        );

        $result = $client->singleLoginConfirm($this->clientId, $code, $token);

        if ($result['status'] == 'ERROR') {
            return null;
        }

        $phone = preg_replace('~\D~', '', $result['userMobilePhone']);

        if (!$phone) {
            $profile = $client->getClientByEmail($result['userEmail']);
            $phone = preg_replace('~^7~', '8', preg_replace('~\D~', '', $profile['phone']));
        }

        $user = new SingleLoginUser($result['userId'], $result['userEmail']);

        $user->setPersistentStates(array(
            'phone' => $phone,
            'userRights' => $result['userRights'],
        ));

        return $user;
    }

    private function getReturnUrl()
    {
        return Yii::app()->controller->createAbsoluteUrl($this->returnRoute);
    }
}


