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
        $price = (int)$this->price;
        $price = (string) number_format($price);

        $oldPrice = (int) $this->oldPrice;
        $oldPrice = (string) number_format($oldPrice);
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $price,
            'oldPrice' => $oldPrice,
            'cover' => $this->cover,
            'url' => $this->url,
            'inventory' => $this->inventory,
            'shortDescription' => $this->shortDescription,
            'description' => $this->description,  
            'salesCount'=> $this->salesCount,
            'countDown'=> $this->countdown,
            'warehouseInventory'=> $this->warehouseInventory,
            'satisfaction'=> $this->satisfaction,
            'additionalInformation'=> $this->additionalInformation,
            'images' => $this->images->pluck('path'), 
            'categoryName' =>  $this->group->name,
            'categoryPath' =>$this->group->url,
            'stars'=>$this->stars,
            'discount'=>$this->activeOffer(),
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
