<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrederProductResource extends JsonResource
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
            'name' => trim($this->name),
            'count'=>$this->quantity,
            'size'=>$this->size,
            'brand'=>$this->brand,
            'pay_type'=>$this->pay_type,
            'color'=>$this->color,
           
            'price'=>number_format($this->product_price),
            'total_price'=>number_format($this->total_price),
            'ratio'=>$this->ratio,
            'discount' => $this->activeOffer()['percent'],
            'cover' => $this->cover,
            'categoryName' => $this->group->name, 
            'sizes' => $this->whenLoaded('sizes', fn() => $this->sizes->pluck('size')),
            'colors' => $this->whenLoaded('colors', fn() => $this->colors->pluck('color')),
            'brands' => $this->whenLoaded('brands', fn() => $this->brands->pluck('name')),
        ];
    }
}
