<?php
use App\Models\Admin\Brand;
use App\Models\Admin\Color;
use App\Models\Admin\CompanyStock;
use App\Models\Admin\Product;
use App\Models\Admin\Size;
 function price($product, $color = null , $brand = null , $size = null, $selectedType = null, $count = null, $user = null){
    
    $color_price = $product->colors->first()?->price * $product->ratio ?? 0;
    
    $brand_price = $product->brands->first()?->price * $product->ratio ?? 0;
    $size_price = $product->sizes->first()?->price * $product->ratio ?? 0;



    
    if ($color):
        
        $color_price = $color->price * $product->ratio;
    endif;
    if ($size):
        $size_price = $size->price * $product->ratio;
    endif;
    if ($brand):
        $brand_price = $brand->price * $product->ratio;
        
    endif;

    
    if (!$user){
        $user = auth()->user();
    }
   
    $price = (int) $product->price;
    if ($product->id == 7) {
      
    }
    $price = ($product->price * $product->ratio) + $color_price + $brand_price + $size_price;

    
    
    if ($user) {

        $category = $user->category;
        
        $checkes = $category->checkRules;
        
        $checksList = [];
        $checksListOld = [];

        foreach ($checkes as $item) {

            if ($item) {
                $checksList['day_' . $item->term_days] = $price + (($price * $item->percent) / 100);

                $checksListOld['day_' . $item->term_days] = $product->activeOffer()['percent'] > 0 ? $price + (($price * $item->percent) / 100) : 0;
                
                $checksList['day_' . $item->term_days] = $product->activeOffer()['percent'] > 0 ? $checksList['day_' . $item->term_days] * ((100 - $product->activeOffer()['percent']) / 100) : $checksList['day_' . $item->term_days];
                
            }
        }

        $prices = [];
        $prices['cash'] = $price;

        $oldPrices['cash'] = $product->activeOffer()['percent'] > 0 ? $price : 0;
        $prices['cash'] = $product->activeOffer()['percent'] > 0 ? $price * ((100 - $product->activeOffer()['percent']) / 100) : $price;
        
        $prices['credit'] = $price + (($price * $category->percent) / 100);
        $oldPrices['credit'] = $product->activeOffer()['percent'] > 0 ? $price  : 0;
        $prices['credit'] = $prices['cash'] + (($prices['cash'] * $category->percent) / 100);
        $prices['checkes'] = $checksList;
        if (isset($prices['checkes'][$selectedType])) {
            $total_product_price = $count ?  $prices['checkes'][$selectedType] * $count :  $prices['checkes'][$selectedType];
            $one_product =  $prices['checkes'][$selectedType];     
        }
        $oldPrices['checkes'] = $checksListOld;
        if ($selectedType == 'credit') {
            $total_product_price = $count ? $prices['credit'] * $count : $prices['credit'];
            $one_product = $prices['credit'];
        }
        $prices['credit'] = isset($prices['credit']) ? number_format($prices['credit']) : 0;



        $oldPrices['credit'] = isset($oldPrices['credit']) ? number_format($oldPrices['credit']) : 0;

    } else {
        $prices['cash'] = number_format($price);
        $oldPrices['cash'] = $product->activeOffer()['percent'] > 0 ? $price : 0;

        $prices['cash'] = $product->activeOffer()['percent'] > 0 ? $price * ((100 - $product->activeOffer()['percent']) / 100) : $price;

    }
    $cash = $product->price;
    $cash = number_format($cash);
    $price = (int) $product->price;

    // $price = $product->activeOffer()['percent'] > 0 ? $price * ((100 - $product->activeOffer()['percent']) / 100) : $price;
    
    
   
    if ($selectedType == 'cash') {
    $total_product_price = $count ? $prices['cash'] * $count : $prices['cash'];
        $one_product = $prices['cash'];
    
    }
    
    
    
    
    
    if (isset($total_product_price)){
        $number_total_product_price = $total_product_price;
        $total_product_price = $total_product_price ? $total_product_price : 0;
        
        
    }
    else{
        $total_product_price = $prices['cash'];
        $number_total_product_price = $total_product_price;
    }

    if (isset($one_product)) {
        
        $number_one_product = $one_product ? $one_product : 0;
        $one_product = $one_product ? $one_product : 0;
        

    } else {
        
        $one_product = $prices['cash'];
        $number_one_product = $one_product ? $one_product : 0;
        
    }
    $prices['cash'] = number_format($prices['cash']);
    $oldPrices['cash'] = number_format($oldPrices['cash']);
    if (!empty($oldPrices['checkes']) && is_array($oldPrices['checkes'])) {
        foreach ($oldPrices['checkes'] as $key => $item) {
            $oldPrices['checkes'][$key] = number_format($item);
        }
    }

    if (!empty($prices['checkes']) && is_array($prices['checkes'])) {
        foreach ($prices['checkes'] as $key => $item) {
            $prices['checkes'][$key] = number_format($item);
        }
    }
   
    $one_product = number_format($one_product);
    $total_product_price = number_format($total_product_price);
    return [
        'prices' => $prices,
        
        'oldPrices'=> $oldPrices,
        'total_price'=> $total_product_price,
        'one_product'=> $one_product,
        'number_total_product_price'=> $number_total_product_price ?? 0,
        'number_one_product'=> $number_one_product ?? 0,
    ];

}




function price_calculate($product, $color = null, $brand = null, $size = null, $selectedType = null, $count = null, $user = null)
{

    $existingProductInCompany = CompanyStock::where('product_id', $product->id)
        ->where(function ($query) use ($color) {
            if (!is_null($color)) {
                $query->where('color_code', $color);
            } 
        })
        ->where(function ($query) use ($size) {
            if (!is_null($size)) {

                $query->where('size', $size);
            } 
        })
        ->where(function ($query) use ($brand) {
            if (!is_null($brand)) {
                $query->where('brand', $brand);
            } 
        })
        ->first();
        // dd($existingProductInCompany, $color, $brand, $size);
        $price = $existingProductInCompany ? ($product->price + $existingProductInCompany->price) * $product->ratio : $product->price * $product->ratio;
        
    
   

   
        // dd($existingProductInCompany);

    if (!$user) {
        $user = auth()->user();
    }

   
  



    if ($user) {

        $category = $user->category;

        $checkes = $category->checkRules;

        $checksList = [];
        $checksListOld = [];

        foreach ($checkes as $item) {

            if ($item) {
                $checksList['day_' . $item->term_days] = $price + (($price * $item->percent) / 100);

                $checksListOld['day_' . $item->term_days] = $product->activeOffer()['percent'] > 0 ? $price + (($price * $item->percent) / 100) : 0;

                $checksList['day_' . $item->term_days] = $product->activeOffer()['percent'] > 0 ? $checksList['day_' . $item->term_days] * ((100 - $product->activeOffer()['percent']) / 100) : $checksList['day_' . $item->term_days];

            }
        }
        
        $prices = [];
        $prices['cash'] = $price;
        
        $oldPrices['cash'] = $product->activeOffer()['percent'] > 0 ? $price : 0;
        $prices['cash'] = $product->activeOffer()['percent'] > 0 ? $price * ((100 - $product->activeOffer()['percent']) / 100) : $price;
        $prices['credit'] = $price + (($price * $category->percent) / 100);
       
        $prices['credit'] = $product->activeOffer()['percent'] > 0 ? $prices['credit'] * ((100 - $product->activeOffer()['percent']) / 100) : $prices['credit'];
        // dd($prices['credit']);
        $oldPrices['credit'] = $product->activeOffer()['percent'] > 0 ? $price + (($price * $category->percent) / 100) : 0;
        // $prices['credit'] = $prices['cash'] + (($prices['cash'] * $category->percent) / 100);
        $prices['checkes'] = $checksList;
        if (isset($prices['checkes'][$selectedType])) {
            $total_product_price = $count ? $prices['checkes'][$selectedType] * $count : $prices['checkes'][$selectedType];
            $one_product = $prices['checkes'][$selectedType];
        }
        $oldPrices['checkes'] = $checksListOld;
        if ($selectedType == 'credit') {
            $total_product_price = $count ? $prices['credit'] * $count : $prices['credit'];
            $one_product = $prices['credit'];
        }
        $prices['credit'] = isset($prices['credit']) ? number_format($prices['credit']) : 0;



        $oldPrices['credit'] = isset($oldPrices['credit']) ? number_format($oldPrices['credit']) : 0;

    } else {
        $prices['cash'] = number_format($price);
        $oldPrices['cash'] = $product->activeOffer()['percent'] > 0 ? $price : 0;

        $prices['cash'] = $product->activeOffer()['percent'] > 0 ? $price * ((100 - $product->activeOffer()['percent']) / 100) : $price;

    }
    $cash = $product->price;
    $cash = number_format($cash);
    $price = (int) $product->price;

    // $price = $product->activeOffer()['percent'] > 0 ? $price * ((100 - $product->activeOffer()['percent']) / 100) : $price;


   
    if ($selectedType == 'cash') {
        $total_product_price = $count ? $prices['cash'] * $count : $prices['cash'];
        $one_product = $prices['cash'];

    }





    if (isset($total_product_price)) {
      
        $number_total_product_price = $total_product_price;
       


    } else {
        $total_product_price = $prices['cash'] * $count;
        $number_total_product_price = $total_product_price;
       
    }

    if (isset($one_product)) {

        $number_one_product = $one_product ? $one_product : 0;
        $one_product = $one_product ? $one_product : 0;

    } else {

        $one_product = $prices['cash'];
        $number_one_product = $one_product ? $one_product : 0;

    }
    $prices['cash'] = number_format($prices['cash']);
    $oldPrices['cash'] = number_format($oldPrices['cash']);
    if (!empty($oldPrices['checkes']) && is_array($oldPrices['checkes'])) {
        foreach ($oldPrices['checkes'] as $key => $item) {
            $oldPrices['checkes'][$key] = number_format($item);
        }
    }

    if (!empty($prices['checkes']) && is_array($prices['checkes'])) {
        foreach ($prices['checkes'] as $key => $item) {
            $prices['checkes'][$key] = number_format($item);
        }
    }
    
    $one_product = number_format($one_product);
    $total_product_price = number_format($total_product_price);
    return [
        'prices' => $prices,

        'oldPrices' => $oldPrices,
        'total_price' => $total_product_price,
        'one_product' => $one_product,
        'number_total_product_price' => $number_total_product_price ?? 0,
        'number_one_product' => $number_one_product ?? 0,
    ];

}