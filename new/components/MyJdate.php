<?php

namespace app\components;

use Yii;
use yii\base\component;

class MyJdate extends component
{

    public $jalali = true; //Use Jalali Date, If set to false, falls back to gregorian
    public $convert = true; //Convert numbers to Farsi characters in utf-8
    public $timezone = 'Asia/Tehran'; //Timezone String e.g Asia/Tehran, Default is GMT
    private $temp = array();


	private function div($a, $b)
    {
        return (int) ($a / $b);
    }


    // input => get gregorian timstamp
    // output => Jalali date e.t 1395/10/17

    public function toJalali($Mdate)
    {

        $Odate = explode("/", date('Y/m/d', $Mdate));
        $g_y = $Odate[0];
        $g_m = $Odate[1];
        $g_d = $Odate[2];

        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

        $gy = $g_y-1600;
        $gm = $g_m-1;
        $gd = $g_d-1;

        $g_day_no = 365*$gy+$this->div($gy+3, 4)-$this->div($gy+99, 100)+$this->div($gy+399, 400);

        for ($i=0; $i < $gm; ++$i)
            $g_day_no += $g_days_in_month[$i];
        if ($gm>1 && (($gy%4==0 && $gy%100!=0) || ($gy%400==0)))
            $g_day_no++;
        $g_day_no += $gd;

        $j_day_no = $g_day_no-79;

        $j_np = $this->div($j_day_no, 12053);
        $j_day_no = $j_day_no % 12053;

        $jy = 979+33*$j_np+4*$this->div($j_day_no, 1461);

        $j_day_no %= 1461;

        if ($j_day_no >= 366) {
            $jy += $this->div($j_day_no-1, 365);
            $j_day_no = ($j_day_no-1)%365;
        }

        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
            $j_day_no -= $j_days_in_month[$i];
        $jm = $i+1;
        $jd = $j_day_no+1;

        return implode("/", array($jy, $jm, $jd));
    }


    // input => Jalali date e.t 1395/02/08
    // output => get Gregorian timstamp

    public function toGregorian($Mdate)
    {
        $Mdate_1 = explode("/", $Mdate);
        $j_y = $Mdate_1[0];
        $j_m = $Mdate_1[1];
        $j_d = $Mdate_1[2];

        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

        $jy = $j_y-979;
        $jm = $j_m-1;
        $jd = $j_d-1;

        $j_day_no = 365*$jy + $this->div($jy, 33)*8 + $this->div($jy%33+3, 4);
        for ($i=0; $i < $jm; ++$i)
            $j_day_no += $j_days_in_month[$i];

        $j_day_no += $jd;

        $g_day_no = $j_day_no+79;

        $gy = 1600 + 400*$this->div($g_day_no, 146097);
        $g_day_no = $g_day_no % 146097;

        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100*$this->div($g_day_no,  36524);
            $g_day_no = $g_day_no % 36524;

            if ($g_day_no >= 365)
                $g_day_no++;
            else
                $leap = false;
        }

        $gy += 4*$this->div($g_day_no, 1461);
        $g_day_no %= 1461;

        if ($g_day_no >= 366) {
            $leap = false;

            $g_day_no--;
            $gy += $this->div($g_day_no, 365);
            $g_day_no = $g_day_no % 365;
        }

        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++)
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
        $gm = $i+1;
        $gd = $g_day_no+1;


        return strtotime(implode("-", array($gy, $gm, $gd)));

    }
    
    public function dateEn($format, $stamp = false, $convert = null, $jalali = null, $timezone = null)
    {

        //Timestamp + Timezone
        $stamp = ($stamp != false) ? $stamp : time();
        $obj = new \DateTime('@' . $stamp);
        // die(var_dump($obj));
        if ( $this->timezone != null || $timezone != null ) {
            // die(var_dump($obj->setTimezone( new \DateTimeZone(($timezone != null) ? $timezone : $this->timezone) ) ));
            $obj->setTimezone( new \DateTimeZone(($timezone != null) ? $timezone : $this->timezone) );
        }
            
        // die(var_dump($obj));
        if ( false ) {
                return $obj->format($format);
        }
        else 
        {
            //Find what to replace
            $chars = (preg_match_all('/([a-zA-Z]{1})/', $format, $chars)) ? $chars[0] : array();
            
            //Intact Keys
            $intact = array('B','h','H','g','G','i','s','I','U','u','Z','O','P');
            $intact = $this->filterArray($chars, $intact);
            // die(var_dump($intact));
            $intactValues = array();

            foreach ($intact as $k => $v) {
                $intactValues[$k] = $obj->format($v);
            }
            // die(var_dump($intactValues));
            //End Intact Keys


            //Changed Keys
            list($year, $month, $day) = array($obj->format('Y'), $obj->format('n'), $obj->format('j'));
            list($jyear, $jmonth, $jday) = $this->toJalalis($year, $month, $day);

            $keys = array('d','D','j','l','N','S','w','z','W','F','m','M','n','t','L','o','Y','y','a','A','c','r','e','T');
            $keys = $this->filterArray($chars, $keys, array('z'));
            $values = array();

            foreach ($keys as $k => $key) {

                $v = '';
                switch ($key) {
                    //Day
                    case 'd':
                        $v = sprintf("%02d", $jday);
                        break;
                    case 'D':
                        $v = $this->getDayNames($obj->format('D'), true);
                        break;
                    case 'j':
                        $v = $jday;
                        break;
                    case 'l':
                        $v = $this->getDayNames($obj->format('l'));
                        break;
                    case 'N':
                        $v = $this->getDayNames($obj->format('l'), false, 1, true);
                        break;
                    case 'S':
                        $v = 'ام';
                        break;
                    case 'w':
                        $v = $this->getDayNames($obj->format('l'), false, 1, true) - 1;
                        break;
                    case 'z':
                        if ($jmonth > 6) {
                            $v = 186 + (($jmonth - 6 - 1) * 30) + $jday;
                        }
                        else {
                            $v = (($jmonth - 1) * 31) + $jday;
                        }
                        $this->temp['z'] = $v;
                        break;
                    //Week
                    case 'W':
                        $v = is_int($this->temp['z'] / 7) ? ($this->temp['z'] / 7) : intval($this->temp['z'] / 7 + 1);
                        break;
                    //Month
                    case 'F':
                        $v = $this->getMonthNames($jmonth);
                        break;
                    case 'm':
                        $v = sprintf("%02d", $jmonth);
                        break;
                    case 'M':
                        $v = $this->getMonthNames($jmonth, true);
                        break;
                    case 'n':
                        $v = $jmonth;
                        break;
                    case 't':
                        $v = ( $jmonth == 12 ) ? 29 : ( ($jmonth > 6 && $jmonth != 12) ? 30 : 31 );
                        break;
                    //Year
                    case 'L':
                        $tmpObj = new \DateTime('@'.(time()-31536000));
                        $v = $tmpObj->format('L');
                        break;
                    case 'o':
                    case 'Y':
                        $v = $jyear;
                        break;
                    case 'y':
                        $v = $jyear % 100;
                        break;
                    //Time
                    case 'a':
                        $v = ($obj->format('a') == 'am') ? 'ق.ظ' : 'ب.ظ';
                        break;
                    case 'A':
                        $v = ($obj->format('A') == 'AM') ? 'قبل از ظهر' : 'بعد از ظهر';
                        break;
                    //Full Dates
                    case 'c':
                        $v  = $jyear.'-'.sprintf("%02d", $jmonth).'-'.sprintf("%02d", $jday).'T';
                        $v .= $obj->format('H').':'.$obj->format('i').':'.$obj->format('s').$obj->format('P');
                        break;
                    case 'r':
                        $v  = $this->getDayNames($obj->format('D'), true).', '.sprintf("%02d", $jday).' '.$this->getMonthNames($jmonth, true);
                        $v .= ' '.$jyear.' '.$obj->format('H').':'.$obj->format('i').':'.$obj->format('s').' '.$obj->format('P');
                        break;
                    //Timezone
                    case 'e':
                        $v = $obj->format('e');
                        break;
                    case 'T':
                        $v = $obj->format('T');
                        break;

                }
                $values[$k] = $v;

            }
            //End Changed Keys

            //Merge
            $keys = array_merge($intact, $keys);
            $values = array_merge($intactValues, $values);

            //Return

            $ret = strtr($format, array_combine($keys, $values));
            return
            ($convert === false ||
                ($convert === null && $this->convert === false) ||
                ( $jalali === false || $jalali === null && $this->jalali === false ))
                ? $ret : $ret;

        }

    }



    public function date($format, $stamp = false, $convert = null, $jalali = null, $timezone = null)
    {

        //Timestamp + Timezone
        $stamp = ($stamp != false) ? $stamp : time();
        $obj = new \DateTime('@' . $stamp);
        // die(var_dump($obj));
        if ( $this->timezone != null || $timezone != null ) {
            // die(var_dump($obj->setTimezone( new \DateTimeZone(($timezone != null) ? $timezone : $this->timezone) ) ));
            $obj->setTimezone( new \DateTimeZone(($timezone != null) ? $timezone : $this->timezone) );
        }
            
        // die(var_dump($obj));
        if ( false ) {
                return $obj->format($format);
        }
        else 
        {
            //Find what to replace
            $chars = (preg_match_all('/([a-zA-Z]{1})/', $format, $chars)) ? $chars[0] : array();
            
            //Intact Keys
            $intact = array('B','h','H','g','G','i','s','I','U','u','Z','O','P');
            $intact = $this->filterArray($chars, $intact);
            // die(var_dump($intact));
            $intactValues = array();

            foreach ($intact as $k => $v) {
                $intactValues[$k] = $obj->format($v);
            }
            // die(var_dump($intactValues));
            //End Intact Keys


            //Changed Keys
            list($year, $month, $day) = array($obj->format('Y'), $obj->format('n'), $obj->format('j'));
            list($jyear, $jmonth, $jday) = $this->toJalalis($year, $month, $day);

            $keys = array('d','D','j','l','N','S','w','z','W','F','m','M','n','t','L','o','Y','y','a','A','c','r','e','T');
            $keys = $this->filterArray($chars, $keys, array('z'));
            $values = array();

            foreach ($keys as $k => $key) {

                $v = '';
                switch ($key) {
                    //Day
                    case 'd':
                        $v = sprintf("%02d", $jday);
                        break;
                    case 'D':
                        $v = $this->getDayNames($obj->format('D'), true);
                        break;
                    case 'j':
                        $v = $jday;
                        break;
                    case 'l':
                        $v = $this->getDayNames($obj->format('l'));
                        break;
                    case 'N':
                        $v = $this->getDayNames($obj->format('l'), false, 1, true);
                        break;
                    case 'S':
                        $v = 'ام';
                        break;
                    case 'w':
                        $v = $this->getDayNames($obj->format('l'), false, 1, true) - 1;
                        break;
                    case 'z':
                        if ($jmonth > 6) {
                            $v = 186 + (($jmonth - 6 - 1) * 30) + $jday;
                        }
                        else {
                            $v = (($jmonth - 1) * 31) + $jday;
                        }
                        $this->temp['z'] = $v;
                        break;
                    //Week
                    case 'W':
                        $v = is_int($this->temp['z'] / 7) ? ($this->temp['z'] / 7) : intval($this->temp['z'] / 7 + 1);
                        break;
                    //Month
                    case 'F':
                        $v = $this->getMonthNames($jmonth);
                        break;
                    case 'm':
                        $v = sprintf("%02d", $jmonth);
                        break;
                    case 'M':
                        $v = $this->getMonthNames($jmonth, true);
                        break;
                    case 'n':
                        $v = $jmonth;
                        break;
                    case 't':
                        $v = ( $jmonth == 12 ) ? 29 : ( ($jmonth > 6 && $jmonth != 12) ? 30 : 31 );
                        break;
                    //Year
                    case 'L':
                        $tmpObj = new \DateTime('@'.(time()-31536000));
                        $v = $tmpObj->format('L');
                        break;
                    case 'o':
                    case 'Y':
                        $v = $jyear;
                        break;
                    case 'y':
                        $v = $jyear % 100;
                        break;
                    //Time
                    case 'a':
                        $v = ($obj->format('a') == 'am') ? 'ق.ظ' : 'ب.ظ';
                        break;
                    case 'A':
                        $v = ($obj->format('A') == 'AM') ? 'قبل از ظهر' : 'بعد از ظهر';
                        break;
                    //Full Dates
                    case 'c':
                        $v  = $jyear.'-'.sprintf("%02d", $jmonth).'-'.sprintf("%02d", $jday).'T';
                        $v .= $obj->format('H').':'.$obj->format('i').':'.$obj->format('s').$obj->format('P');
                        break;
                    case 'r':
                        $v  = $this->getDayNames($obj->format('D'), true).', '.sprintf("%02d", $jday).' '.$this->getMonthNames($jmonth, true);
                        $v .= ' '.$jyear.' '.$obj->format('H').':'.$obj->format('i').':'.$obj->format('s').' '.$obj->format('P');
                        break;
                    //Timezone
                    case 'e':
                        $v = $obj->format('e');
                        break;
                    case 'T':
                        $v = $obj->format('T');
                        break;

                }
                $values[$k] = $v;

            }
            //End Changed Keys

            //Merge
            $keys = array_merge($intact, $keys);
            $values = array_merge($intactValues, $values);

            //Return

            $ret = strtr($format, array_combine($keys, $values));
            return
            ($convert === false ||
                ($convert === null && $this->convert === false) ||
                ( $jalali === false || $jalali === null && $this->jalali === false ))
                ? $ret : $this->convertNumbers($ret);

        }

    }


    private function filterArray($needle, $heystack, $always = array())
    {
        foreach($heystack as $k => $v)
        {
            if( !in_array($v, $needle) && !in_array($v, $always) )
                unset($heystack[$k]);
        }
        
        return $heystack;
    }

    private function convertNumbers($matches)
    {
        $farsi_array = array("۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹");
        $english_array = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
        return str_replace($english_array, $farsi_array, $matches);
    }

    public function toJalalis($g_y, $g_m, $g_d)
    {

        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

        $gy = $g_y-1600;
        $gm = $g_m-1;
        $gd = $g_d-1;

        $g_day_no = 365*$gy+$this->div($gy+3, 4)-$this->div($gy+99, 100)+$this->div($gy+399, 400);

        for ($i=0; $i < $gm; ++$i)
            $g_day_no += $g_days_in_month[$i];
        if ($gm>1 && (($gy%4==0 && $gy%100!=0) || ($gy%400==0)))
            $g_day_no++;
        $g_day_no += $gd;

        $j_day_no = $g_day_no-79;

        $j_np = $this->div($j_day_no, 12053);
        $j_day_no = $j_day_no % 12053;

        $jy = 979+33*$j_np+4*$this->div($j_day_no, 1461);

        $j_day_no %= 1461;

        if ($j_day_no >= 366) {
            $jy += $this->div($j_day_no-1, 365);
            $j_day_no = ($j_day_no-1)%365;
        }

        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
            $j_day_no -= $j_days_in_month[$i];
        $jm = $i+1;
        $jd = $j_day_no+1;

        return array($jy, $jm, $jd);

    }
}
