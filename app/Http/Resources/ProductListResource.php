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
        $color_name = $this->colors->first()?->color ?? null;
        $brand_name = $this->brands->first()?->name ?? null;
        $size_name = $this->sizes->first()?->size ?? null;
        
        $prices = price($this);

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
            'salesCount' => $this->salesCount,
            'countDown' => $this->activeOffer()['countDown'],
            'warehouseInventory' => $this->warehouseInventory,
            'satisfaction' => (int)$this->satisfaction,
            'additionalInformation' => trim($this->additionalInformation),
            'images' => $this->whenLoaded('images', fn() => $this->images->pluck('path')),
            'categoryName' => $this->whenLoaded('group', fn() => trim($this->group->name)),
            'categoryPath' => $this->whenLoaded('group', fn() => trim($this->group->name)),
            'category_id' => $this->whenLoaded('group', fn() => trim($this->group->id)),
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
            'update' => $this->updated_at?->format('Y-m-d H:i:s'),
            'defaults' => ['color' => $color_name, 'size' => $size_name, 'brand' => $brand_name]
        ];
    }
}
