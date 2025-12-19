<?php
namespace app\components;

use app\models\PaymentVal;
use Yii;
use yii\base\component;
use yii\base\Exception;
// use app\components\lib\nusoap_client;
use SoapClient;
require_once(Yii::$app->basePath."/components/lib/nusoap.php");

class IranKish extends component
{
	public $terminalId ;
	public $password ;
	public $acceptorId ;
	public $pub_key ;

    public function init()
	{
		$payment = PaymentVal::findOne(['payment_id' => 3]);
		$this->terminalId = $payment->terminalId;
		$this->password = $payment->password;
		$this->acceptorId = $payment->acceptorId;
		$this->pub_key = $payment->pub_key;
	}

	public function GoBank($amount, $paymentId, $url)
	{
        $token = $this->generateAuthenticationEnvelope($amount);
        
        $data = [];
        $data["request"] = [
            "acceptorId" => $this->acceptorId,
            "amount" => $amount,
            "billInfo" => null,
            "paymentId" => strval($paymentId),
            "requestId" => strval(time()),
            "requestTimestamp" => time(),
            "revertUri" => $url,
            "terminalId" => $this->terminalId,
            "transactionType" => "Purchase"
        ];
        $data['authenticationEnvelope'] = $token;
        $data_string = json_encode($data);
        $ch = curl_init('https://ikc.shaparak.ir/api/v3/tokenization/make');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));
        
        
        
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result, JSON_OBJECT_AS_ARRAY);
        
        if ($response["responseCode"] != "00") {
            echo $response["description"];
            exit;
        }
        
        $html = '<form method="post" id="myForm" action="https://ikc.shaparak.ir/iuiv3/IPG/Index/" enctype="‫‪multipart/form-data‬‬">
                    <input type="hidden" name="tokenIdentity" value="'.$response["result"]["token"].'" style="display:none">
                    <input type="submit" value="DoPayment" style="display:none">
                </form>
                <script language="javascript" type="text/javascript">
                	document.getElementById("myForm").submit();;
                </script>';
        print $html;
	}
	
	
	public function generateAuthenticationEnvelope($amount)
    {
        $data = $this->terminalId . $this->password . str_pad($amount, 12, '0', STR_PAD_LEFT) . '00';
        $data = hex2bin($data);
        $AESSecretKey = openssl_random_pseudo_bytes(16);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, $cipher, $AESSecretKey, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash('sha256', $ciphertext_raw, true);
        $crypttext = '';
        
        // die(var_dump($password));
        openssl_public_encrypt($AESSecretKey . $hmac, $crypttext, $this->pub_key);
    
        return array(
            "data" => bin2hex($crypttext),
            "iv" => bin2hex($iv),
        );
    }
    
    public function Verify($retrievalReferenceNumber, $systemTraceAuditNumber, $token)
    {
        $data = array(
            "terminalId" => $this->terminalId,
            "retrievalReferenceNumber" => $retrievalReferenceNumber,
            "systemTraceAuditNumber" => $systemTraceAuditNumber,
            "tokenIdentity" => $token,
        );
    
        $data_string = json_encode($data);
    
    
        $ch = curl_init('https://ikc.shaparak.ir/api/v3/confirmation/purchase');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));
    
    
    
        $result = curl_exec($ch);
        if ($result === false) {
            echo curl_error($ch);
            exit;
        }
        curl_close($ch);
    
        $response = json_decode($result, JSON_OBJECT_AS_ARRAY);
        return $response;
    }

}