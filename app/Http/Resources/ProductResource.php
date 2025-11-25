<?php

namespace App\Http\Resources;

use App\Models\Admin\Product;
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
        $user = auth()->user();
        $price = (int) $this->price;
        $price = $this->price * $this->ratio;
        if ($user) {

            $category = $user->category;
            
            $checkes = $category->checkRules;
            $checksList = [];

            foreach ($checkes as $item) {
                
                if ($item){
                    $checksList['day_'.$item->term_days] = $price + (($price * $item->percent) / 100);
                    
                    $checksListOld['day_'.$item->term_days] = $price + (($price * $item->percent) / 100);
                    $checksListOld['day_'.$item->term_days] = number_format($checksListOld['day_' . $item->term_days]);
                    $checksList['day_' . $item->term_days] = $this->activeOffer()['percent'] > 0 ? $checksList['day_' . $item->term_days] * ((100 - $this->activeOffer()['percent']) / 100) : $checksList['day_' . $item->term_days];
                    $checksList['day_' . $item->term_days] = number_format($checksList['day_' . $item->term_days]);
                }
            }
          
            $prices = [];
            $prices['cash'] = $price;
            $oldPrices['cash'] = $price;
            $prices['cash'] = $this->activeOffer()['percent'] > 0 ? $price * ((100 - $this->activeOffer()['percent']) / 100) : $price;
            $prices['credit'] = $price + (($price * $category->percent) / 100);
            $oldPrices['credit'] = $price + (($price * $category->percent) / 100);
            $prices['credit'] = $prices['cash'] + (($prices['cash'] * $category->percent) / 100);
            $prices['checkes'] = $checksList;
            $oldPrices['checkes'] = $checksListOld;

            
        }
        else {
            $prices['cash'] = number_format($price);
            $oldPrices['cash'] = number_format($price);
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
        $prices['credit'] = number_format($prices['credit']);
        $oldPrices['cash'] = number_format($oldPrices['cash']);
        $oldPrices['credit'] = number_format($oldPrices['credit']);
        
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
        ];
    }

   
}
