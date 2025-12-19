<?php

namespace app\components;

use app\models\Mylog;
use Yii;
use yii\base\component;
use app\models\Visit;
use DateTime;

class MyLogs extends component
{
    public function get_ip()    
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
    }
    public function saveaction($action,$part)
    {
        $date = intval(time()) - (30 * 24 * 60 * 60);
        Mylog::deleteAll(
            ['<', 'date', $date]
       );
        $mylog=new Mylog();
        $mylog->ip=$this->get_ip();
        $mylog->user_id=Yii::$app->user->id;
        $mylog->date=strval(time());
        $mylog->action=$action;
        $mylog->part=$part;
        if(!$mylog->save()){
            die(var_dump($mylog->errors));
        }

    }
}
