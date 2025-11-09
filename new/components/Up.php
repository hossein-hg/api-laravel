<?php
namespace app\components;

use app\models\PaymentVal;
use Yii;
use yii\base\component;
use yii\base\Exception;
// use app\components\lib\nusoap_client;
// use app\components\Gateway;
use SoapClient;
use Gateway;

require_once(Yii::$app->basePath."/components/lib/nusoap.php");
require_once(Yii::$app->basePath."/components/Gateway.php");

class Up extends component
{
	
	public $MerchantID;
	public $userName;
	public $Password;
	public $CallbackURL;

	public function init()
	{
		$payment = PaymentVal::findOne(['payment_id' => 5]);
		$this->MerchantID = $payment->MerchantID;
		$this->userName = $payment->userName;
		$this->Password = $payment->password;
		$this->CallbackURL = $payment->CallbackURL;
	}

	public function GoBank($amount, $orderId, $back)
	{
	    $Username = $this->userName;
	    $Password = $this->Password;
	    $merchantConfigID =$this->MerchantID;
	    
	    $CurUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    	$CurUrl = substr($CurUrl,0, strrpos($CurUrl, '/')+1);
    	$CallBackUrl = $back;	
    
        $result = Gateway::make()
            ->config($Username,$Password,$merchantConfigID,$CallBackUrl)
            ->amount($amount)
            ->invoiceId($orderId)
            ->token();
    
        if($result['code'] == 200){	
            Gateway::redirect($result['content'],"");
        }
    	else{
    		if ($result['errortype']){
    			echo 
    			'<div class="error">
    				<span style="color: #d00">خطای ماژول CURL.<br>
    				کدخطا: <b>'.$result['code'].'</b></span>
    				<p align="right">شرح خطا:</p>
    				<div style="text-align: left; direction: ltr;">
    					<span style="font:bold 11pt verdana ">'.$result['content'].'</span>
    				</div>		
    			</div>';		
    			exit();
    		}
            echo 
    		'<div class="error">
    			<span style="color: #d00">خطا هنگام ایجاد تراکنش.<br>
    			کدخطا: <b>'.$result['code'].'</b></span>
    			<div style="text-align:right;">
    				شرح خطا:<br><span style="direction:ltr; font:bold 11pt verdana ">'.$result['content'].'</span></div>
    				<div style="text-align:justify; direction:rtl; line-height:1.4;  margin-top:30px">برای دریافت شرح کاملتر خطا با مراجعه به نشانی 
    				<a href="https://rest.asanpardakht.net" target="_blank">https://rest.asanpardakht.net</a> ، شرح خطای <b>'.$result['code'].'</b> 
    				را در متد <b>Token</b> مشاهده کنید.
    			</div>		
    		</div>';
        }
    }
    
    
    public function VerifyBank($invoice, $payGateTranID)
	{
	    $Username = $this->userName;
	    $Password = $this->Password;
	    $merchantConfigID = $this->MerchantID;
	    
		$gateway = Gateway::make()
            ->config($Username,$Password,$merchantConfigID)
            ->invoiceId($invoice);
        $result = $gateway->TranResult();
        
        if($result['code'] != 200)
        {
    		/*echo 'مشکل در TranResult تراکنش';
    		echo '<br>';
    		echo 'Http Code: '.$result['code'];
    		echo '<br>';
    		echo 'Response: '.$result['content'];
    		var_dump($result);
    		exit();*/
    		return 0;
    	}
    	
    	/*echo "<hr>";
    	var_dump($result['content']);
    	echo "<hr>";*/
    	
    	$verify = $gateway->verify($payGateTranID);
        if($verify['code'] == 200)
        {
            
            /*echo 'تراکنش verify شد.';
            echo '<br>';*/
    		//Settlement
            $settlement = $gateway->settlement($payGateTranID);
            if($settlement['code'] == 200){
                /*echo 'تراکنش settlement شد.';
                echo '<br>';*/
                return 1;
            }
    		else
    		{
                /*echo 'مشکل در settlement تراکنش';
                echo '<br>';
                echo 'Http Code: '.$settlement['code'];
                echo '<br>';
                echo 'Response: '.$settlement['content'];*/
                return 0;
            }
        }
    	else
        {
            return 0;
            /*echo 'مشکل در verify تراکنش';
            echo '<br>';
            echo 'Http Code: '.$verify['code'];
            echo '<br>';
            echo 'Response: '.$verify['content'];*/
        }
        return 0;
	}

}