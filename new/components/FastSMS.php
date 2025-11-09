<?php

/*namespace app\components;

use Yii;
use SoapClient;
use yii\base\component;
use yii\base\Exception;

class FastSMS extends component
{
    public function send($name, $mobile)
    {
        $client = new SoapClient("http://37.130.202.188/class/sms/wsdlservice/server.php?wsdl");
        $user = "maxgroup";
        $pass = "fgh456qaz";
        $fromNum = "+98100020400";
        $toNum = array($mobile);
        $pattern_code = "337";
        $input_data = array(
            "sitename" => "فروشگاه اینترنتی عطرنبی",
            "password"  => $name,
        );
        $client->sendPatternSms($fromNum,$toNum,$user,$pass,$pattern_code,$input_data);
    }       
}

*/

namespace app\components;

use Yii;
use SoapClient;
use yii\base\component;
use yii\base\Exception;

class FastSMS extends component
{
    public $username = 'maxgroup';
    public $pass = 'fgh456qaz';
    public $number = '+98100020400';


    public function send($name, $mobile)
    {
        $info = Yii::$app->SiteInfo->Mysite();
        
        $client = new SoapClient("http://188.0.240.110/class/sms/wsdlservice/server.php?wsdl");
        $user = "maxgroup";
        $pass = "fgh456qaz";
        $fromNum = "+98100020400";
        $toNum = array($mobile);
        $pattern_code = "cs9nvg3ltp";
        $input_data = array(
            "name" => $info->fa_name,
            "password"  => $name,
        );
        $client->sendPatternSms($fromNum,$toNum,$user,$pass,$pattern_code,$input_data);
    }       
}


