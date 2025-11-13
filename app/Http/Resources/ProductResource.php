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
        $filters = $this->group->filters ?? null;
        $selectedOptions = $this->options()->get()->keyBy('pivot.filter_id');
        $result = [];
        if ($filters) {
        foreach ($filters as $filter) {
            $result[] = [
                'filter' => $filter,
                'option' => $selectedOptions->has($filter->id) ? $selectedOptions[$filter->id] : null
            ];
        }
    }

        // Format (اختیاری)
        $features = collect($result)->map(function ($item) {
            return 
                $item['filter']->name." ".$item['option']->name
            ;
        }); 
       
        
        // dd($result[1]['filter']->name, $result[1]['option']->name);   
         
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
            'features' => $features,
            'update' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
