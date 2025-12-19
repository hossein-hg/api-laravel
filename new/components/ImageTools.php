<?php

namespace app\components;

use Yii;
use yii\base\component;
use yii\imagine\Image;
use Imagine\Gd;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
// use yii\imagine\Image;
// use Imagine\Image\ImageInterface;

class ImageTools extends component
{
    public function Crop($path, $width, $height)
    {
        // پیدا کردن ابعاد جدید برای برش تصویر
        list($main_width, $main_height) = getimagesize($path);
        if($main_height >= $height && $main_width >= $width)
        {
            if($main_width > $main_height)
            {
                $new_Height = intval($main_height);
                $new_Width = intval(($main_height * $width) / $height);
            }
            else
            {
                $new_Width = intval($main_width);
                $new_Height = intval(($main_width * $height) / $width);
            }
        }
        elseif($main_height >= $height && $main_width < $width)
        {
            $new_Width = intval($main_width);
            $new_Height = intval(($main_width * $height) / $width);
        }
        elseif($main_height < $height && $main_width >= $width)
        {
            $new_Height = intval($main_height);
            $new_Width = intval(($main_height * $width) / $height);
        }
        elseif($main_height < $height && $main_width < $width)
        {
            if($main_width > $main_height)
            {
                $new_Height = intval($main_height);
                $new_Width = intval(($main_height * $width) / $height);
            }
            else
            {
                $new_Width = intval($main_width);
                $new_Height = intval(($main_width * $height) / $width);
            }
        }
        else
        {
            $new_Width = intval($main_width);
            $new_Height = intval($main_height);
        }
        
        
        // درصورت بزرگتر شدن ابعاد جدید از ابعاد اصلی تصویر
        if($main_width < $new_Width)
        {
            $new_Width = intval($main_width);
            $new_Height = intval(($main_width * $height) / $width);
        }
        elseif($main_height < $new_Height)
        {
            $new_Height = intval($main_height);
            $new_Width = intval(($main_height * $width) / $height);
        }
        
        
        if($main_width >= $new_Width)
        {
            $o_width = ($main_width - $new_Width) / 2;
        }
        else
        {
            $o_width = ($new_Width - $main_width) / 2;
        }
        
        if($main_height >= $new_Height)
        {
            $o_height = ($main_height - $new_Height) / 2;
        }
        else
        {
            $o_height = ($new_Height - $main_height) / 2;
        }
        
        /*echo "<pre>";
        var_dump($new_Width);
        var_dump($new_Height);
        var_dump($main_width);
        var_dump($main_height);
        var_dump($o_width);
        var_dump($o_height);
        die();*/
            
        $thumbnail = Image::thumbnail($path, $new_Width, $new_Height);
        
        Image::crop($path, $new_Width, $new_Height, [$o_width, $o_height])
            ->save($path, ['quality' => 100]);


        Image::getImagine()->open($path, $width, $height)
            ->thumbnail(new Box($width, $height))
            ->save($path , ['quality' => 100]);
    }
}