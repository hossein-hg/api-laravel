<?php

namespace App\Services;

use SoapClient;
use Exception;

class SmsService
{
    protected $username = 'maxgroup';
    protected $password = 'fgh456qaz4540';
    protected $senderNumber = '+985000107070000';
    // protected $senderNumber = '+98100020400';
    // protected $senderNumber = '+9810004150535353';
    protected $client;
    protected $mobiles;
    protected $message;

    public function __construct()
    {
        $this->client = new SoapClient("http://188.0.240.110/class/sms/wssimple/server.php?wsdl");
        $this->client->soap_defencoding = 'UTF-8';
        $this->client->decode_utf8 = true;
        error_log('متدهای موجود: ' . print_r($this->client->__getFunctions(), true));
       
        
    }

    public function send($message, $mobiles)
    {

        // $client = new SoapClient("http://188.0.240.110/class/sms/wsdlservice/server.php?wsdl");
        // $user = "maxgroup";
        // $pass = "fgh456qaz4540";
        // $fromNum = "+98100020400";
        // $toNum = array($mobiles);
        // $pattern_code = "cs9nvg3ltp";
        // $input_data = array(
        //     "name" => 'test',
        //     "password" => $message,
        // );
        // return $client->sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_code, $input_data);



















        $mobiles = is_array($mobiles) ? $mobiles : [$mobiles];
        // اصلاح شماره‌ها (هر کدوم رو جداگانه correct کن)
        $correctedMobiles = [];
        foreach ($mobiles as $singleMobile) {
            $corrected = $this->correctNumber($singleMobile);
            if (!empty($corrected)) {
                $correctedMobiles[] = $corrected;
            }
        }
        // $mobiles = [+989375434086];

        if (is_array($mobiles)) {
            $i = sizeOf($mobiles);

            while ($i--) {
                $mobiles[$i] = self::CorrectNumber($mobiles[$i]);
            }
        } else {
            $mobiles = array(self::CorrectNumber($mobiles));
        }
        $this->mobiles = $mobiles;
        $this->message = $message;
        
        $params = [
            $this->username,
            $this->password,
           
            $this->senderNumber,   
            $message,
            $mobiles,
            'normal',
        ];
       
        $response = $this->call('SendSMS', $params);
        var_dump($response);
    }

    private function call($method, $params)
    {
        // $result = call_user_func_array([$this->client, 'SendSMS'], "Amir");


        // die(var_dump($this->mob));
        try {
            return call_user_func_array([$this->client, $method], [
                $this->username,
                $this->password,
                $this->senderNumber,
                $this->mobiles,
                $this->message,
                "normal",
            ]);
        } catch (SoapFault $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }

        $result = $this->client->__call($method, $params);

        if($this->client->fault || ((bool)$this->client->getError()))
        {
        	return array('error' => true, 'fault' => true, 'message' => $this->client->getError());
        }

        return $result;
    }

    public static function CorrectNumber(&$uNumber)
    {
        $uNumber = Trim($uNumber);
        $ret = &$uNumber;
        // die(var_dump($ret));

        if (substr($uNumber, 0, 3) == '%2B') {
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }

        if (substr($uNumber, 0, 3) == '%2b') {
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }

        if (substr($uNumber, 0, 4) == '0098') {
            $ret = substr($uNumber, 4);
            $uNumber = $ret;
        }

        if (substr($uNumber, 0, 3) == '098') {
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }


        if (substr($uNumber, 0, 3) == '+98') {
            $ret = substr($uNumber, 3);
            $uNumber = $ret;
        }

        if (substr($uNumber, 0, 2) == '98') {
            $ret = substr($uNumber, 2);
            $uNumber = $ret;
        }

        if (substr($uNumber, 0, 1) == '0') {
            $ret = substr($uNumber, 1);
            $uNumber = $ret;
        }

        return '+98' . $ret;
    }

    public function getBalance()
    {
        try {
            $params = [
                $this->username,
                $this->password,
            ];
            $response = $this->call('GetCredit', $params);  // یا 'GetUserBalance' اگر WSDL فرق داره
            return $response;  // مثلاً 1500 (موفق) یا [1, 'خطا'] (ناموفق)
        } catch (Exception $e) {
            \Log::error('Balance Check Error: ' . $e->getMessage());  // اگر لاراول
            throw new Exception('خطا در چک موجودی: ' . $e->getMessage());
        }
    }

    public function getDeliveryStatus($uniqueId)
    {
        if (!is_array($uniqueId)) {
            $uniqueId = [$uniqueId];  // [1265165260]
        }
        $batchId = 0;  // خالی برای تکی (یا '0' اگر کار نکرد)

        try {
            $params = [
                $this->username,
                $this->password,
                $batchId,      // BatchID: خالی
                $uniqueId,     // UniqueIDs: آرایه
            ];

            error_log('Status Params: ' . json_encode($params));  // لاگ در PHP (یا \Log::info در لاراول)

            $response = $this->call('GetStatus', $params);
            return $response;  // اگر null داد، یعنی وضعیت آپدیت نشده
        } catch (SoapFault $e) {
            error_log('SOAP Fault Details: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
            throw new Exception('خطا GetStatus: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

   
}
