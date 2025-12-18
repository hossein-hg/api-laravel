<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        
        return [
            // 'product_name' => $this->name,
            'image' => trim($this->image),
            'link' => trim($this->link),
            'delete'=> false,
            'type'=> $this->type,
        ];
    }
}
