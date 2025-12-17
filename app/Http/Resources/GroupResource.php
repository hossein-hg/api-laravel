<?php

namespace App\Http\Resources;

use App\Models\Admin\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       
        $categories = Group::whereNull("parent_id")->except($this)->get();
        foreach ($categories as $category) {
            $oneCat[] = [
                "id"=> $category->id,
                "name"=> $category->name,
            ];
        }
        return [
                "id"=>  $this->id,
                "name"=>  $this->name,
                "level"=>  $this->level,
                "parent_id"=>  $this->parent_id,
                "status"=>  $this->status,
                "url"=>  $this->url,
                "image"=>  $this->image,
                "description"=>  $this->description,
                "keyword"=>  $this->keyword,
                "turn"=>  $this->turn,
                "flag"=>  $this->flag, 
                "color"=>  $this->color,
                "created_at"=>  $this->created_at,
                "updated_at"=>  $this->updated_at,
                "rest_categories"=> $oneCat ?? null,
                "categories" => GroupResource::collection($this->whenLoaded('children')),
            'brands' => $this->when(
                !is_null($this->parent_id), 
                BrandResource::collection($this->brands)
            ),

        ];
    }
}
