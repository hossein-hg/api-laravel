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
        
        
        $color = request()->query('color') ? Color::where('color', $request_color)->where('product_id', $this->id)->first() : null;
        $size = request()->query('size') ? Size::where('size', $request_size)->where('product_id',)->first() : null;
        $brand = request()->query('brand') ? Brand::where('name', $request_brand)->where('product_id',)->first() : null;
        $color_name = $this->colors->first()?->color ?? null;
        $brand_name = $this->brands->first()?->name ?? null;
        $size_name = $this->sizes->first()?->size ?? null;

        $prices = price($this, $color, $size, $brand);
        
        return [
            'id' => $this->id,
            'name' => trim($this->name),
            'price' => $prices['prices'],
            'oldPrice' => $prices['oldPrices'],
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
