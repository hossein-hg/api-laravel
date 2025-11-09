<?php
namespace app\components;

use app\models\PaymentVal;
use Yii;
use yii\base\component;
use yii\base\Exception;
// use app\components\lib\nusoap_client;
use SoapClient;

require_once(Yii::$app->basePath."/components/lib/nusoap.php");

class Tejarat extends component
{
	public $merchantId = 'J5D8';
	

	public $MerchantID;
	public $sha1Key;

	public function init()
	{
		$payment = PaymentVal::findOne(['payment_id' => 4]);
		$this->MerchantID = $payment->MerchantID;
		$this->sha1Key = $payment->acceptorId;
	
	}
	public function GoBank($amount, $getid, $url)
	{

		$client = new \SoapClient('https://ikc.shaparak.ir/XToken/Tokens.xml');
		
		$params['amount'] = strval($amount);
        $params['merchantId'] = $this->merchantId;
        $params['invoiceNo'] = strval($getid);
        $params['paymentId'] = "12345678";
        $params['specialPaymentId'] = "123456789123";
        $params['revertURL'] = $url;
        $params['description'] = "10KALA";
        $result = $client->__soapCall("MakeToken", array($params));
        
        echo "لطفا کمی صبر کنید...";
		echo '<script language="javascript" type="text/javascript">
					var form = document.createElement("form");
					form.setAttribute("method", "POST");
					form.setAttribute("action", "https://ikc.shaparak.ir/TPayment/Payment/index"); 
					form.setAttribute("target", "_self");
					var hiddenField = document.createElement("input"); 
					hiddenField.setAttribute("name", "token");
					hiddenField.setAttribute("value", "'. $result->MakeTokenResult->token .'");
					form.appendChild(hiddenField);
					
					var hiddenField2 = document.createElement("input"); 
					hiddenField2.setAttribute("name", "merchantId");
					hiddenField2.setAttribute("value", "'. $this->merchantId .'");
					form.appendChild(hiddenField2);

					document.body.appendChild(form); 
					form.submit();
					document.body.removeChild(form);
			  </script>';

	}//END FUNCTION GOBANK



	public function VerifyBank($token, $referenceId)
	{
		$client = new SoapClient('https://ikc.shaparak.ir/XVerify/Verify.xml', array('soap_version'   => SOAP_1_1));
        $params['token'] =  $token; // please replace currentToken
        $params['merchantId'] =  $this->merchantId;
        $params['referenceNumber'] = $referenceId;
        $params['sha1Key'] = $this->sha1Key;
        $result = $client->__soapCall("KicccPaymentsVerification", array($params));
        return $result;
	}
}