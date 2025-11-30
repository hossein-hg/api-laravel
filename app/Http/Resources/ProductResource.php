<?php

namespace App\Http\Resources;

use App\Models\Admin\Brand;
use App\Models\Admin\Color;
use App\Models\Admin\Product;
use App\Models\Admin\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public static $wrap = null;
    public function toArray(Request $request): array
    {
        $request_color = request()->query('color');
        $request_size = request()->query('size');
        $request_brand = request()->query('brand');
        $request_count = request()->query('count');
        
        $color = request()->query('color') ? Color::where('color', $request_color)->where('product_id', $this->id)->first() : null;
        $size = request()->query('size') ? Size::where('size', $request_size)->where('product_id',)->first() : null;
        $brand = request()->query('brand') ? Brand::where('name', $request_brand)->where('product_id',)->first() : null;
        $color_price = $this->colors->first()?->price * $this->ratio ?? 0;
        $brand_price = $this->brands->first()?->price * $this->ratio ?? 0;
        $size_price = $this->sizes->first()?->price * $this->ratio ?? 0;
        if ($color) :  $color_price = $color->price * $this->ratio; endif;
        if ($size) :  $size_price = $size->price * $this->ratio; endif;
        if ($brand) :  $brand_price = $brand->price * $this->ratio; endif;
        
        
        $color_name = $this->colors->first()?->color  ?? null;
        $brand_name = $this->brands->first()?->name  ?? null;
        $size_name = $this->sizes->first()?->size  ?? null;
        $user = auth()->user();
        $price = (int) $this->price;

        $price = ($this->price * $this->ratio) + $color_price + $brand_price + $size_price;
        if ($request_count) : $price = ($this->price * $this->ratio * (int) $request_count) + $color_price + $brand_price + $size_price; endif;
        
        if ($user) {

            $category = $user->category;
            
            $checkes = $category->checkRules;
            $checksList = [];

            foreach ($checkes as $item) {
                
                if ($item){
                    $checksList['day_'.$item->term_days] = $price + (($price * $item->percent) / 100);
                    
                    $checksListOld['day_'.$item->term_days] = $this->activeOffer()['percent'] > 0 ? $price + (($price * $item->percent) / 100) : 0;
                    $checksListOld['day_'.$item->term_days] = number_format($checksListOld['day_' . $item->term_days]);
                    $checksList['day_' . $item->term_days] = $this->activeOffer()['percent'] > 0 ? $checksList['day_' . $item->term_days] * ((100 - $this->activeOffer()['percent']) / 100) : $checksList['day_' . $item->term_days];
                    $checksList['day_' . $item->term_days] = number_format($checksList['day_' . $item->term_days]);
                }
            }
            
            $prices = [];
            $prices['cash'] = $price;
            
            $oldPrices['cash'] = $this->activeOffer()['percent'] > 0 ? $price : 0;
            $prices['cash'] = $this->activeOffer()['percent'] > 0 ? $price * ((100 - $this->activeOffer()['percent']) / 100) : $price;
            $prices['credit'] = $price + (($price * $category->percent) / 100);
            $oldPrices['credit'] = $this->activeOffer()['percent'] > 0 ? $price + (($price * $category->percent) / 100) : 0;
            $prices['credit'] = $prices['cash'] + (($prices['cash'] * $category->percent) / 100);
            $prices['checkes'] = $checksList;
            $oldPrices['checkes'] = $checksListOld;
            $prices['credit'] = number_format($prices['credit']);
            $oldPrices['credit'] = number_format($oldPrices['credit']);

        }
        else {
            $prices['cash'] = number_format($price);
            $oldPrices['cash'] = $this->activeOffer()['percent'] > 0 ? $price : 0;
            
            $prices['cash'] = $this->activeOffer()['percent'] > 0 ? $price * ((100 - $this->activeOffer()['percent']) / 100) : $price;

        }
        $cash = $this->price;
        $cash = number_format($cash);
        $price = (int) $this->price;
       
        // $price = $this->activeOffer()['percent'] > 0 ? $price * ((100 - $this->activeOffer()['percent']) / 100) : $price;
        $oldPrice = $this->activeOffer()['percent'] > 0 ? $this->price : 0;

        
        $price = (string) number_format($price);

       
        $oldPrice = (string) number_format($oldPrice);
        $prices['cash'] = number_format($prices['cash']);


        $oldPrices['cash'] = number_format($oldPrices['cash']);
        return [
            'id' => $this->id,
            'name' => trim($this->name),
            'price' => $prices,
            'oldPrice' => $oldPrices,
            'cover' => $this->cover,
            'url' => $this->url,
            'inventory' => $this->inventory,
            'shortDescription' => trim($this->shortDescription),
            'description' => trim($this->description),  
            'salesCount'=> $this->salesCount,
            'countDown' => $this->activeOffer()['countDown'],
            'warehouseInventory'=> $this->warehouseInventory,
            'satisfaction' => (int) $this->satisfaction,
            'additionalInformation'=> $this->additionalInformation,
            'images' => $this->images->pluck('path'), 
            'categoryName' =>  trim($this->group->name),
            'categoryPath' => trim($this->group->name),
            'stars'=>$this->stars,
            'discount' => $this->activeOffer()['percent'],
            'tags' => $this->tags,
            'features' => $this->filtersWithSelectedOptions(),
            'ratio'=>$this->ratio,
            'comments'=> $this->comments->pluck('body'),
            'warranties'=>  $this->warranties->pluck('name'),
            'sizes'=> $this->sizes->pluck('size'),
            'colors'=> $this->colors->pluck('color'),
            'brands'=> $this->brands->pluck('name'),
            'commentsCount'=>$this->comments()->count(),
            'related_products'=>Product::where('group_id',$this->group_id)->get()->except($this->id),
            'update' => $this->updated_at->format('Y-m-d H:i:s'),
            'defaults'=> ['color'=> $color_name, 'size'=> $size_name, 'brand'=> $brand_name]
        ];
    }

   
}
