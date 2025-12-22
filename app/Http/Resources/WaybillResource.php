<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaybillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "order_id" => $this->code,
            "name" => $this->name,
            "mobile" => $this->mobile,
            "plate" => $this->plate,
            "description" => $this->description,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
