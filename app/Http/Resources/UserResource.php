<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "phone"=> $this->phone,
            "telephone"=> $this->telephone,
            "gender"=> $this->gender,
            "category_name"=> $this->category->name ?? "1",
            "avatar"=> $this->avatar,
            "user_type"=> $this->user_type,
            "company_name"=> $this->company_name,
            "national_code"=> $this->national_code,
            "economic_code"=> $this->economic_code,
            "registration_number"=> $this->registration_number,
            "is_active"=> $this->is_active == 1 ? true : false,
        ];
    }
}
