<?php

namespace app\components;

use Yii;
use yii\base\component;
// use yii\imagine\Image;
// use Imagine\Image\ImageInterface;

class ImageTools2 extends component
{
    public $xim;
    public $yim; 
    
    public function Crop($path, $name, $width, $height, $last_path)
    {
        $path = $path;
        $info = getimagesize($path);
        $extension = image_type_to_extension($info[2]);
        // die(var_dump($extension));
        
        if($extension == ".png")
        {
            $im = imagecreatefrompng($path);
        }
        else
        {
            $im = imagecreatefromjpeg($path);
        }
        
        $size = min(imagesx($im), imagesy($im));
	
    	// Check Iphone And Rotate
    	/*$data = exif_read_data($path);
    	if(!empty($data['Orientation'])) {
    	    switch($data['Orientation']) {
    	        case 8:
    	            $im = imagerotate($im ,90,0);
    	            break;
    	        case 3:
    	            $im = imagerotate($im ,180,0);
    	            break;
    	        case 6:
    	            $im = imagerotate($im ,-90,0);
    	            break;
    	    }
    	}*/


	
	
	    // Crop Image	
        if(imagesy($im) > imagesx($im) && $width != 0 && $height != 0)
        {
            $this->xim = imagesx($im);
            $this->yim = imagesx($im);
            $param = (($height * imagesx($im)) / $width);

            if($param > imagesx($im))
            {
                $im2 = imagecrop($im, ['x' => (imagesx($im) - $width)/2 , 'y' => (imagesy($im) - $height)/2 , 'width' => $width, 'height' => $height]);
            }
            else
            {
                $im2 = imagecrop($im, ['x' => 0 , 'y' => (imagesy($im) - $param)/2 , 'width' => $this->xim, 'height' => $param]);
            }
        }
        elseif (imagesy($im) < imagesx($im) && $width != 0 && $height != 0)
        {
            $this->xim = imagesy($im);
            $this->yim = imagesy($im);
            $param = (($width * imagesy($im)) / $height);

            if($param > imagesx($im))
            {
                $im2 = imagecrop($im, ['x' => (imagesx($im) - $width)/2 , 'y' => (imagesy($im) - $height)/2 , 'width' => $width, 'height' => $height]);
            }
            else
            {
                $im2 = imagecrop($im, ['x' => (imagesx($im) - $param)/2 , 'y' => 0 , 'width' => $param, 'height' => $this->yim]);
            }
            
        }
        elseif (imagesy($im) == imagesx($im) && $width != 0 && $height != 0)
        {
            $this->xim = imagesx($im);
            $this->yim = imagesy($im);
            $param = (($width * imagesy($im)) / $height);

            $im2 = imagecrop($im, ['x' => (imagesx($im) - $param)/2 , 'y' => 0 , 'width' => $param, 'height' => $this->yim]);
        }
        else
        {
            if($extension == ".png")
            {
                $im2 = imagecreatefrompng($path);
            }
            else
            {
                $im2 = imagecreatefromjpeg($path);
            }
        }
        
    
        
        if ($im2 !== FALSE)
        {
            

            // FIND SIZE WHEN WITHE OR HEIGHT IS AUTO
            
            if($extension == ".png")
            {
                imagepng($im2, getcwd(). $last_path .$name);
                $src = imagecreatefrompng(getcwd(). $last_path .$name);
            }
            else
            {
                imagejpeg($im2, getcwd(). $last_path .$name);
                $src = imagecreatefromjpeg(getcwd(). $last_path .$name);
            }
            
            
            if($height == 0)
            {
                $height = (($width * imagesy($im)) / imagesx($src));
            }
            elseif($width == 0)
            {
                $width = (($height * imagesx($im)) / imagesy($src));
            }

            // Resize Image
    	    $dst = imagecreatetruecolor($width, $height);
    	    imagealphablending( $dst, false );
            imagesavealpha( $dst, true );
            
            
            if($width != imagesx($src) || $height != imagesy($src))
            {
                $im2 = imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));
                if($extension == ".png")
                {
                    imagepng($dst, getcwd(). $last_path .$name, 9);
                }
                else
                {
                    imagejpeg($dst, getcwd(). $last_path .$name);
                }
            }
	     
        }
        else
        {
            die(var_dump('expression'));
        }
    }
}