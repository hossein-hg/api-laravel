<?php

namespace app\components;

use Yii;
use yii\base\component;
// use yii\imagine\Image;
// use Imagine\Image\ImageInterface;

class SiteInfo extends component
{
    public function Mysite()
    {
        return \app\models\Details::findOne(1);
    }
}