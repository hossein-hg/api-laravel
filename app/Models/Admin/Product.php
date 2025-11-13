<?php

namespace App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Admin\Category;
use App\Models\Admin\Comment;
use App\Models\Admin\Image;
use App\Models\Admin\Feature;

class Product extends Model
{
    // protected function casts(): array
    // {
    //     return [
    //         'images' => 'array',
    //         'tags' => 'array',
    //         'scoresSection' => 'array',
            
            
    //     ];
    // }
    use HasFactory;
    protected $fillable = [
        'name',
        'stars',
        'url',
        'category_id',
        'price',
        'oldPrice',
        'cover',
        'inventory',
        'shortDescription',
        'salesCount',
        'description',
        'countdown',
        'warehouseInventory',
        'satisfaction',
        'additionalInformation',
    ];

    public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

    public function images(){
        return $this->hasMany(Image::class,'product_id','id');
    }

    public function features()
    {
        return $this->hasMany(Feature::class, 'product_id', 'id');
    }

    public function group(){
        return $this->belongsTo(Group::class,'group_id');
    }

    public function options()
    {
        return $this->belongsToMany(Option::class, 'option_product')->withPivot('filter_id');
    }

    public function filtersWithSelectedOptions()
    {
        $filters = $this->group->filters ?? null;
        $selectedOptions = $this->options()->get()->keyBy('pivot.filter_id');
        $result = [];
        if ($filters) {
            foreach ($filters as $filter) {
                $result[] = [
                    'filter' => $filter,
                    'option' => $selectedOptions->has($filter->id) ? $selectedOptions[$filter->id] : null
                ];
            }
        }

        return collect($result)->map(function ($item) {
            return
                $item['filter']->name . " " . $item['option']->name
            ;
        });

    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }




}
