<?php

namespace App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

use App\Models\Admin\Category;

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


}
