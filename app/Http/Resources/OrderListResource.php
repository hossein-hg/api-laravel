<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
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
            
            'name' => $this->whenLoaded('user', fn() => trim($this->user->name)),
            'order_status'=> $this->status,
            'payment_status'=> $this->payment_status,
            'total_price'=> $this->total_price,
            'created_at'=> $this->created_at->format('Y-m-d H:i:s'),     
        ];
    }
}
