<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
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
            'salesCount'=> $this->salesCount,
            'countDown'=> $this->countdown,
            'warehouseInventory'=> $this->warehouseInventory,
            'satisfaction'=> $this->satisfaction,
            'additionalInformation'=> $this->additionalInformation,
            'images' => $this->whenLoaded('images', fn() => $this->images->pluck('path')), 
            'categoryName' => $this->whenLoaded('category', fn() => $this->category->name),
            'categoryPath' => $this->whenLoaded('category', fn() => $this->category->path),
            'stars'=>$this->stars,
            'features' => $this->filtersWithSelectedOptions(),
            'ratio'=>$this->ratio,
            'comments'=> $this->whenLoaded('comments', fn() => $this->comments->pluck('body')),
            'update' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
