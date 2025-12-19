<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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

            'image' => 'https://files.epyc.ir/images/avatar.jpg',
            'name' => $this->whenLoaded('user', fn() => trim($this->user->name)),
            'stars' => 5,
            'content'=>$this->body,
        ];
    }
}
