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
            'quantity'=>$this->quantity,
            'price'=>$this->price,
            'total_price'=>$this->total_price,
            'ratio'=>$this->ratio,
            'discount' => $this->activeOffer()['percent'],
           
        ];
    }
}
