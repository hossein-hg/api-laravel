<?php

namespace App\Http\Resources;
use App\Models\Admin\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    
    public static $wrap = null;
    public function toArray(Request $request): array
    {
        $price = (int) $this->price;
        $price = $this->activeOffer()['percent'] > 0 ? $price * ((100-$this->activeOffer()['percent'])/100) : $price;
        $oldPrice = $this->activeOffer()['percent'] > 0 ? $this->price : 0;

        
        $price = (string) number_format($price);
        
        
        $oldPrice = (string) number_format($oldPrice);
        return [
            'id' => $this->id,
            'name' => trim($this->name),
            'price' => $price,
            'oldPrice' => $oldPrice,
            'cover' => $this->cover,
            'url' => $this->url,
            'inventory' => $this->inventory,
            'shortDescription' => trim($this->shortDescription),
            'description' => trim($this->description),
            'salesCount' => $this->salesCount,
            'countDown' => $this->activeOffer()['countDown'],
            'warehouseInventory' => $this->warehouseInventory,
            'satisfaction' => $this->satisfaction,
            'additionalInformation' => trim($this->additionalInformation),
            'images' => $this->whenLoaded('images', fn() => $this->images->pluck('path')),
            'categoryName' => $this->whenLoaded('group', fn() => trim($this->group->name)),
            'categoryPath' => $this->whenLoaded('group', fn() => trim($this->group->name)),
            'stars' => $this->stars,
            'discount' => $this->activeOffer()['percent'],
            'tags' => $this->tags,
            'features' => $this->filtersWithSelectedOptions(),
            'ratio' => $this->ratio,
            'comments' => $this->whenLoaded('comments', fn() => $this->comments->pluck('body')),
            'warranties' => $this->whenLoaded('warranties', fn() => $this->warranties->pluck('name')),
            'sizes' => $this->whenLoaded('sizes', fn() => $this->sizes->pluck('size')),
            'colors' => $this->whenLoaded('colors', fn() => $this->colors->pluck('color')),
            'brands' => $this->whenLoaded('brands', fn() => $this->brands->pluck('name')),
            'commentsCount' => $this->whenLoaded('comments', fn(): mixed => $this->comments()->count()),
            'related_products' => Product::where('group_id', $this->group_id)->get()->except($this->id)->pluck('name'),
            'update' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
