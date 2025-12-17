<?php

namespace App\Http\Resources;
use Illuminate\Http\Request;
use App\Models\Admin\Product;
use App\Models\Admin\CompanyStock;
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
        
     
        $price_calculate = price_calculate($this);
        $existingProductInCompany = CompanyStock::where('product_id', $this->id)
            ->where(function ($query) use ($color_name) {
                if (!is_null($color_name)) {
                    $query->where('color_code', $color_name);
                }
            })
            ->where(function ($query) use ($size_name) {
                if (!is_null($size_name)) {

                    $query->where('size', $size_name);
                }
            })
            ->where(function ($query) use ($brand_name) {
                if (!is_null($brand_name)) {
                    $query->where('brand', $brand_name);
                }
            })
            ->first();
        $count_company = $existingProductInCompany ? $existingProductInCompany->count() : 0;
        $inventory = $this->inventory;
        if ($this->type == 1 and $count_company == 0) {
            $inventory = 0;
        }
        return [
            'id' => $this->id,
            'name' => trim($this->name),
            'en_name' => trim($this->en_name),
            'price' => $price_calculate['prices'],
            'oldPrice' => $price_calculate['oldPrices'],
            'cover' => $this->cover,
            'url' => $this->url,
            'inventory' => $inventory,
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
            'subCategory_id' => $this->whenLoaded('group', fn() => trim($this->group->id)),
            'stars' => $this->stars,
            'discount' => $this->activeOffer()['percent'],
            'discount_start_time' => $this->offer ? $this->offer->start_time : null,
            'discount_end_time' => $this->offer ? $this->offer->end_time : null,
            'discount_percent'=> $this->offer ? $this->offer->percent : 0,
            'tags' => $this->tags,
            'features' => $this->filtersWithSelectedOptions(),
            'ratio' => $this->ratio,
            'max_sell' => $this->max_sell,
            'type'=> $this->type,
            'comments' => $this->whenLoaded('comments', fn() => $this->comments->pluck('body')),
            'warranties' => $this->whenLoaded('warranties', fn() => $this->warranties->pluck('name')),
            'sizes' => $this->whenLoaded('sizes', fn() => $this->sizes->pluck('size')),
            'colors' => $this->whenLoaded('colors', fn() => $this->colors->pluck('color')),
            'brands' => $this->whenLoaded('brands', fn() => $this->brands->pluck('name')),
            'commentsCount' => $this->whenLoaded('comments', fn(): mixed => $this->comments()->count()),
            'related_products' => Product::where('group_id', $this->group_id)->get()->except($this->id)->pluck('name'),
            'update' => $this->updated_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s') ?? '',
            'defaults' => ['color' => $color_name, 'size' => $size_name, 'brand' => $brand_name]
        ];
    }
}
