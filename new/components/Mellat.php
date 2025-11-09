<?php
namespace app\components;

use app\models\PaymentVal;
use Yii;
use yii\base\component;
use yii\base\Exception;
// use app\components\lib\nusoap_client;
use SoapClient;
require_once(Yii::$app->basePath."/components/lib/nusoap.php");

class Mellat extends component
{
	
	public $terminalId;
	public $userName;
	public $userPassword;
	public $CallbackURL;

	public function init()
	{
		$payment = PaymentVal::findOne(['payment_id' => 2]);
		$this->terminalId = $payment->terminalId;
		$this->userName = $payment->userName;
		$this->userPassword = $payment->userPassword;
		$this->CallbackURL = $payment->CallbackURL;
	}
	public function GoBank($orderId, $amount, $amir)
	{

		$client = new \nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
		$namespace='http://interfaces.core.sw.bps.com/';


		$date =  date("Ymd");
		$time =  date("His");

		// Check for an error
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}

		$parameters = array(
			'terminalId' => $this->terminalId,
			'userName' => $this->userName,
			'userPassword' => $this->userPassword,
			'orderId' => $orderId,
			'amount' => $amount,
			'localDate' => $date,
			'localTime' => $time,
			'additionalData' => "10Pay",
			'callBackUrl' =>$amir,
			'payerId' => 0
		);

		

		// Call the SOAP method
		$result = $client->call('bpPayRequest', $parameters, $namespace);
		// echo "<pre>";
		// die(var_dump($result));

		// Check for a fault
		if ($client->fault) 
		{
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			echo 'خطا در اتصال به بانک';
			echo '</pre>';
			die();
		}
		else
		{
			// Check for errors
			$resultStr  = $result;

			$err = $client->getError();
			if ($err) 
			{
				// Display the error
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				echo '<h2>Error</h2><pre>خطا در اتصال به بانک</pre>';
				die();
			}
			else
			{
				$res = explode (',',$resultStr);
				// echo "<script>alert('Pay Response is : " . $resultStr . "');</script>";
				// echo "Pay Response is : " . $resultStr;
				$ResCode = $res[0];
				
				if ($ResCode == "0") 
				{
					// Update table, Save RefId
					// echo "<script language='javascript' type='text/javascript'>postRefId('" . $res[1] . "');</script>";
					// die(var_dump($res[1]));
					echo "لطفا کمی صبر کنید...";
					echo '<script language="javascript" type="text/javascript">
								var form = document.createElement("form");
								form.setAttribute("method", "POST");
								form.setAttribute("action", "https://bpm.shaparak.ir/pgwchannel/startpay.mellat"); 
								form.setAttribute("target", "_self");
								var hiddenField = document.createElement("input"); 
								hiddenField.setAttribute("name", "RefId");
								hiddenField.setAttribute("value", "'. $res[1] .'");
								form.appendChild(hiddenField);

								document.body.appendChild(form); 
								form.submit();
								document.body.removeChild(form);
						  </script>';
				} 
				else
				{
				    echo '<pre>' . $ResCode . '</pre>';
					echo '<h2>Error</h2><pre>خطا در اتصال به بانک</pre>';
					die();
				}

			}//END SEND TO BANK
		}//END FATAL ERROR

	}//END FUNCTION GOBANK



	public function VerifyBank($orderId, $referenceId)
	{
		$client = new \nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
		$namespace='http://interfaces.core.sw.bps.com/';

		// Check for an error
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}
			  
		$parameters = array(
			'terminalId' => $this->terminalId,
			'userName' => $this->userName,
			'userPassword' => $this->userPassword,
			'orderId' => $orderId,
			'saleOrderId' => $orderId,
			'saleReferenceId' => $referenceId
		);

		// Call the SOAP method
		$result = $client->call('bpVerifyRequest', $parameters, $namespace);

		// Check for a fault
		if ($client->fault) 
		{
			echo '<h2>Fault</h2><pre>';
			print_r($result);
// 			echo "خطا در محاسبات";
			echo '</pre>';
			die();
		} 
		else
		{
			$resultStr = $result;
	
			$err = $client->getError();
			if ($err) 
			{
				// Display the error
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				// echo '<h2>Error</h2><pre>خطا در محاسبات...</pre>';
				die();
			} 
			else 
			{
				// echo "<script>alert('Verify Response is : " . $resultStr . "');</script>";
				// echo "Verify Response is : " . $resultStr;
				// echo "<hr>";

				return $resultStr;

			}//END DISPLAY RESULT
		}//END CHECK FOR ERRORS
	}//END VERIFY FUNCTION



	public function Settle($orderId, $referenceId)
	{
		$client = new \nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
		$namespace='http://interfaces.core.sw.bps.com/';

		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}

		$parameters = array(
			'terminalId' => $this->terminalId,
			'userName' => $this->userName,
			'userPassword' => $this->userPassword,
			'orderId' => $orderId,
			'saleOrderId' => $orderId,
			'saleReferenceId' => $referenceId
		);

		// Call the SOAP method
		$result = $client->call('bpSettleRequest', $parameters, $namespace);

		// Check for a fault
		if ($client->fault) 
		{
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			// echo "خطا در محاسبات";
			echo '</pre>';
			die();
		}
		else
		{
			$resultStr = $result;
			$err = $client->getError();
			if ($err) {
				// Display the error
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				// echo "خطا در محاسبات";
				die();
			}
			else
			{
				// echo "<script>alert('Settle Response is : " . $resultStr . "');</script>";
				// echo "Settle Response is : " . $resultStr;

				return $resultStr;

			}//END DISPLAY RESULT
		}//END CHECK FOR ERRORS
	}//END SETTLE FUNCTION
	

}