<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'ip'=> $this->ip,
            'user'=> $this->user ? $this->user->name : null,
            'role'=> $this->role,
            'description'=> $this->description,
            'created_at'=> $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
