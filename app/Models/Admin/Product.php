<?php

namespace App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Admin\Category;
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
        // همه فیلترهای گروه
        $filters = $this->group->filters;
        
        
       
        // لیست گزینه‌های انتخاب شده محصول
        $selectedOptions = $this->options()->get()->keyBy('pivot.filter_id');

        // آماده کردن آرایه نهایی
        $result = [];
        foreach ($filters as $filter) {
            $result[] = [
                'filter' => $filter,
                'option' => $selectedOptions->has($filter->id) ? $selectedOptions[$filter->id] : null
            ];
        }
       
        return $result;
    }




}
