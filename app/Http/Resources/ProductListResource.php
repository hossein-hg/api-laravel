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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'oldPrice' => $this->oldPrice,
            'cover' => $this->cover,
            'url' => $this->url,
            'inventory' => $this->inventory,
            'shortDescription' => $this->shortDescription,
            'description' => $this->description,
            'salesCount' => $this->salesCount,
            'countDown' => $this->countdown,
            'warehouseInventory' => $this->warehouseInventory,
            'satisfaction' => $this->satisfaction,
            'additionalInformation' => $this->additionalInformation,
            'images' => $this->whenLoaded('images', fn() => $this->images->pluck('path')),
            'categoryName' => $this->whenLoaded('group', fn() => $this->group->name),
            'categoryPath' => $this->whenLoaded('group', fn() => $this->group->url),
            'stars' => $this->stars,
            'discount' => $this->activeOffer(),
            'tags' => $this->tags,
            'features' => $this->filtersWithSelectedOptions(),
            'ratio' => $this->ratio,
            'comments' => $this->whenLoaded('comments', fn() => $this->comments->pluck('body')),
            'warranties' => $this->whenLoaded('warranties', fn() => $this->warranties->pluck('name')),
            'sizes' => $this->whenLoaded('sizes', fn() => $this->sizes->pluck('size')),
            'colors' => $this->whenLoaded('colors', fn() => $this->colors->pluck('name')),
            'brands' => $this->whenLoaded('brands', fn() => $this->brands->pluck('name')),
            'commentsCount' => $this->whenLoaded('comments', fn(): mixed => $this->comments()->count()),
            'related_products' => Product::where('group_id', $this->group_id)->get()->except($this->id)->pluck('name'),
            'update' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
