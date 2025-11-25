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
            'color'=>$this->color,
            'price'=>number_format((int)$this->price),
            'total_price'=>number_format((int)$this->total_price),
            'ratio'=>$this->ratio,
            'discount' => $this->activeOffer()['percent'],
            'cover' => $this->cover,
            'categoryName' => $this->category_name,
           
        ];
    }
}
