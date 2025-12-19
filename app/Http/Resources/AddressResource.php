<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'id'=> $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'mobile' => $this->mobile,
            'phone' => $this->phone,
            'province' => $this->province,
            'city' => $this->city,
            'address' => $this->address,
            'postalCode' => $this->code,
            'email' => $this->email,
            'description' => $this->description,
        ];
    }
}
