<?php

namespace app\components;

use Yii;
use SoapClient;
use yii\base\component;
use yii\base\Exception;

class SMS extends component
{
    public $username = 'maxgroup';
    public $pass = 'fgh456qaz4540';
    public $number = '+985000107070000';
    // public $number = '+985000131935';


    public function send($name, $mobile)
    {
        $gate = new SMSService($this->username, $this->pass);
        // $resp = $gate->GetUserBalance();
        $resp = $gate->SendSMS($name, $this->number, $mobile);
    }       
}


// SMSService CLASS REQUIRED FOR SEND SMS
class SMSService
{
    private $Username = "";
    private $Password = "";
    private $client   = null;
    private $mob;
    private $SNumber;
    private $payam;

    function  __construct($user, $pass)
    {
        $this->Username = $user;
        $this->Password = $pass;

        $this->client = new SoapClient("http://188.0.240.110/class/sms/wssimple/server.php?wsdl");

        // die(var_dump($this->client->__call));

        // var_dump($this->client);

        $this->client->soap_defencoding = 'UTF-8';
        $this->client->decode_utf8 = true;
    }
    
    public function SendSMS($Message, $SenderNumber, $Receptors, $type = 'normal')
    {

        if(is_array($Receptors))
        {
            $i = sizeOf($Receptors);
            
            while($i--)
            {
                $Receptors[$i] =  self::CorrectNumber($Receptors[$i]);
            }
        }
        else
        {
            $Receptors = array(self::CorrectNumber($Receptors));
        }

        $this->mob = $Receptors;
        $this->SNumber = $SenderNumber;
        $this->payam = $Message;

        $params = array(
            $this->Username,
            $this->Password,
            $this->mob,
            $SenderNumber,
            $Message,
            $type,
        );

        // die(var_dump($params));

        $response = $this->call("SendSMS", $params);

        return $response;
    }
    
    public function GetStatus($BatchID, $UniqueIDs)
    {
        $params = array(
            'Username' => $this->Username,
            'Password' => $this->Password,
            'BatchID' => $BatchID,
            'UniqueIDs'=> $UniqueIDs
        );



        $response = $this->call("GetStatus", $params);

        return $response;
    }
    
    public function GetMaxReceptors()
    {
        $response = $this->call("GetMaxReceptors", array());
            
        return $response;
    }
    
    public function GetUserBalance()
    {
        $response = $this->call("GetCredit", array('Username' => $this->Username, 'Password' => $this->Password));
            
        return $response;
    }

    private function call($method, $params)
    {
        // $result = call_user_func_array([$this->client, 'SendSMS'], "Amir");


        // die(var_dump($this->mob));
        try {
            return call_user_func_array([$this->client, $method], [
                $this->Username,
                $this->Password,
                $this->SNumber,
                $this->mob,
                $this->payam,
                "normal",
            ]);
        } catch (SoapFault $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }

        // $result = $this->client->__call($method, $params);

            // if($this->client->fault || ((bool)$this->client->getError()))
            // {
            //  return array('error' => true, 'fault' => true, 'message' => $this->client->getError());
            // }

        // return $result;
    }

    public static function CorrectNumber(&$uNumber)
    {
        $uNumber = Trim($uNumber);
        $ret = &$uNumber;
        // die(var_dump($ret));
        
        if (substr($uNumber,0, 3) == '%2B')
        { 
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }
        
        if (substr($uNumber,0, 3) == '%2b')
        { 
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }
        
        if (substr($uNumber,0, 4) == '0098')
        { 
            $ret = substr($uNumber, 4);
            $uNumber = $ret;
        }
        
        if (substr($uNumber,0, 3) == '098')
        { 
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }
        
        
        if (substr($uNumber,0, 3) == '+98')
        { 
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }
        
        if (substr($uNumber,0, 2) == '98')
        { 
            $ret = substr($uNumber, 2);
            $uNumber = $ret;
        }
        
        if(substr($uNumber,0, 1) == '0')
        { 
            $ret = substr($uNumber, 1);
            $uNumber = $ret;
        }  
           
        return '+98' . $ret;
    }
}
