<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = (float)$this->price;
        return [
           
            "color"=> [
                "name"=> $this->color_name,
                "code"=> $this->color_code,
            ],
            "brand" => [
                "name" => $this->brand,
                "id" => $this->id,
            ],
            "size"=> $this->size,
            "price"=> number_format($price),
            "warranty"=> $this->warranty,
            "count"=> $this->count,
            "accCode"=> $this->accCode,
        ];
    }
}
