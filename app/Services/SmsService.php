<?php

namespace App\Services;

use SoapClient;
use Exception;
use Illuminate\Support\Facades\Log;  // اگر Laravel

class SmsService
{
    protected $username = 'afradade003';
    protected $password = 'hosseiny003';
    protected $senderNumber = '+983000505';
    protected $mobiles;
    protected $message;
    protected $client;

    public function __construct()
    {
        // $this->client = new SoapClient("https://ippanel.com/class/sms/wssimple/server.php?wsdl");
        $this->client = new SoapClient("https://ippanel.com/class/sms/wsdlservice/server.php?wsdl");
        // $this->client = new SoapClient("http://188.0.240.110/class/sms/wssimple/server.php?wsdl");
        $this->client->soap_defencoding = 'UTF-8';
        $this->client->decode_utf8 = true;
    }

    public function send($message, $mobiles)
    {
        $this->mobiles = is_array($mobiles) ? $mobiles : [$mobiles];
        $mobiles = is_array($mobiles) ? $mobiles : [$mobiles];

        $correctedMobiles = [];
        foreach ($mobiles as $singleMobile) {
            $corrected = $this->correctNumber($singleMobile);
            if (!empty($corrected)) {
                $correctedMobiles[] = $corrected;
            }
        }
        $mobiles = $correctedMobiles;

        $this->mobiles = $mobiles;
        $this->message = 'کد ورود به سایت : '.$message;

        $params = [
            $this->username,
            $this->password,
            $this->senderNumber,
            $this->mobiles,
            $this->message,
            'normal',
        ];
        
        Log::info('SMS Params: ' . json_encode($params));

        $response = $this->call('SendSMS', $params);
        var_dump($response);

        return $response;
    }

    private function call($method, $params)
    {
        try {
            return call_user_func_array([$this->client, $method], $params);
        } catch (Exception $e) {
            Log::error('SOAP Error: ' . $e->getMessage());
            throw new Exception('خطا در ' . $method . ': ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public static function correctNumber($uNumber)  // renamed to non-static for consistency
    {
        $uNumber = trim($uNumber);
        $ret = $uNumber;

        // حذف %2B یا + از ابتدا
        if (substr($uNumber, 0, 3) == '%2B' || substr($uNumber, 0, 3) == '%2b') {
            $ret = substr($uNumber, 3);
        } elseif (substr($uNumber, 0, 4) == '0098') {
            $ret = substr($uNumber, 4);
        } elseif (substr($uNumber, 0, 3) == '098' || substr($uNumber, 0, 3) == '+98') {
            $ret = substr($uNumber, 3);
        } elseif (substr($uNumber, 0, 2) == '98') {
            $ret = substr($uNumber, 2);
        } elseif (substr($uNumber, 0, 1) == '0') {
            $ret = substr($uNumber, 1);
        }

        return '+98' . $ret;  // فرمت نهایی: +989xxxxxxxxx
    }

    // getBalance و getDeliveryStatus بدون تغییر (اما params رو چک کن)
    public function getBalance()
    {
        try {
            $params = [$this->username, $this->password];
            $response = $this->call('GetCredit', $params);
            Log::info('Balance: ' . json_encode($response));
            return $response;
        } catch (Exception $e) {
            Log::error('Balance Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getDeliveryStatus($uniqueId)
    {
        if (!is_array($uniqueId))
            $uniqueId = [$uniqueId];
        $batchId = 0;  // برای تکی

        try {
            $params = [
                $this->username,
                $this->password,
                $batchId,
                $uniqueId,
            ];
            Log::info('Status Params: ' . json_encode($params));
            $response = $this->call('GetStatus', $params);
            Log::info('Status Response: ' . json_encode($response));
            return $response;
        } catch (Exception $e) {
            Log::error('Status Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendWithPattern($message ,$mobiles)
    {
        $this->mobiles = is_array($mobiles) ? $mobiles : [$mobiles];
        $pattern_code = "6vxjzm7se343a6f";
        $input_data = array(
            "code" => $message,
           
        );
        $params = [$this->senderNumber, $mobiles, $this->username, $this->password, $pattern_code, $input_data];
        return $this->call('sendPatternSms', $params);
        
    }
}