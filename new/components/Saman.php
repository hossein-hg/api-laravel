<?php
namespace app\components;

use app\models\PaymentVal;
use Yii;
use yii\base\component;
use yii\base\Exception;
// use app\components\lib\nusoap_client;
use SoapClient;

require_once(Yii::$app->basePath."/components/lib/nusoap.php");

class Saman extends component
{
	public  $mid; // شماره مشتری بانک سامان
	public $pass;

	public function init()
	{
		$payment = PaymentVal::findOne(['payment_id' => 6]);
		$this->mid = $payment->terminalId;
		$this->pass = $payment->password;
	}

	public function GoBank($order, $amount)
	{

		
		echo "لطفا کمی صبر کنید...";
		echo '<form action="https://sep.shaparak.ir/Payment.aspx" id="myForm" method="POST" style="display: none">
                <input type="hidden" id="Amount" name="Amount" value="'.$amount.'"> <!-- مبلغ -->
                <input type="hidden" id="MID" name="MID" value="'.$this->mid.'"> <!-- شماره مشتری بانک سامان -->
                <input type="hidden" id="ResNum" name="ResNum" value="'.$order.'"> <!-- شماره فاکتور -->
                <input type="hidden" id="RedirectURL" name="RedirectURL" value="https://www.offzadim.ir/orders/backbank"> <!-- آدرس بازگشت -->
                <input type=submit value="pay">
            </form>';
            
		echo '<script language="javascript" type="text/javascript">
				window.onload=function(){
                    document.forms["myForm"].submit();
                }
			  </script>';
				


	}//END FUNCTION GOBANK



	public function VerifyBank($amount, $ref_num)
	{
		try
        {
            $soapclient = new \nusoap_client('https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL','wsdl');
            $soapProxy = $soapclient->getProxy() ;
    
            $mid = $this->mid; // شماره مشتری بانک سامان
            $pass = $this->pass; // پسورد بانک سامان
            $result = $soapProxy->VerifyTransaction($ref_num,$mid);
        }
        catch(Exception $e)
        {
            echo "خطا در اتصال به وبسرویس ";
            die;
        }
        if($result != ($amount))
        {
            // مغایرت مبلغ پرداختی
            if($result<0)
            {
                echo "کد خطای بانک سامان $result ";
                die;
            }
    
            // مغایرت و برگشت دادن وجه به حساب مشتری
            if($result>0)
            {
                return -111;
                // echo "شما باید مبلغ {$data->amount} ریال را پرداخت میکردید در صورتیکه مبلغ {$result}ریال را پرداخت کردید ! مبلغ شما به حسابتان برگشت داده شد آخرین بارتان باشد !!!";
                // $soapProxy->ReverseTransaction($ref_num,$mid,$pass,$result);
            }
        }
        
        if($result == ($amount))
        {
            return 100;
        }
	}//END VERIFY FUNCTION



	/*public function Settle($orderId, $referenceId)
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
	}*///END SETTLE FUNCTION
	

}