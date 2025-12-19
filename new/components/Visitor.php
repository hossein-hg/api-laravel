<?php

namespace app\components;

use Yii;
use yii\base\component;
use app\models\Visit;

class Visitor extends component
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

    public function check()
    {
        $date = intval(time()) - (30 * 24 * 60 * 60);
        $date2= date("Y-m-d", $date);

        Visit::deleteAll(
             ['<', 'date', $date2]
        );
    	$param = Visit::find()->where(['ip'=>$this->get_ip(), 'date'=>date("Y-m-d 00:00:00")])->one();
    	if($param == null)
    	{
    		$model = new Visit;
    		$model->ip = $this->get_ip();
    		$model->date = date("Y-m-d 00:00:00");
    		if(!$model->save())
    		{
    			die(var_dump($model->errors));
    		}
    	}

    }

    public function visitor($param)
    {
    	return Visit::find()->where(['date'=>$param])->count();
    }
    public function MostVisit()
    {
        $result=[];
        $aaa = Visit::find()
            ->groupBy('date')
            ->all();
        for($i=0;$i<count($aaa);$i++){
           array_push($result,Visit::find()->where(['date'=>$aaa[$i]->date])->count());
        }
        return $result;   
    }
}