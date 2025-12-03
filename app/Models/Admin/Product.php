<?php

namespace App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Admin\Category;
use App\Models\Admin\Comment;
use App\Models\Admin\Image;
use App\Models\Admin\Feature;

use App\Models\Admin\Offer;
use App\Models\Admin\Warranty;
use App\Models\Admin\Size;
use App\Models\Admin\Color;
use App\Models\Admin\Brand;
use App\Models\Admin\FilterOption;
use DB;
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

    // public function getRouteKeyName()
    // {
    //     return 'slug';
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
            $filter = $item['filter'] ? $item['filter']->name : '';
            $option = $item['option'] ? $item['option']->name : '';
            if ($option != ''){
                return
                    $filter." ".$option;
            }
        })->filter() // این خط null ها را حذف می‌کند
            ->values();

    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function offer(){
        return $this->hasOne(Offer::class);
    }

    public function activeOffer(){
        $offer = $this->offer;
        
        if ($offer) {
            $startTime = Carbon::parse($offer->start_time);  
            $endTime = Carbon::parse($offer->end_time);  
            $now = Carbon::now();
            
           if ($startTime < $now && $now < $endTime && $this->inventory == 1) {
                $countDown = strtotime($offer->end_time) * 1000;
                    return [
                        'percent'=>$offer->percent,
                        'countDown'=> $countDown
                    ]; 
                
                }
                return [
                        'percent'=>0,
                        'countDown'=> 0
                        ] ;            
            }
            return [
                        'percent'=>0,
                        'countDown'=> 0
                        ] ;
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class, 'product_id', 'id');
    }

    public function sizes()
    {
        return $this->hasMany(Size::class, 'product_id', 'id');
    }

    public function colors()
    {
        return $this->hasMany(Color::class, 'product_id', 'id');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class, 'product_id', 'id');
    }

    protected function casts(): array
    {
        return [
            'tags' => 'array', 
          
        ];
    }

    public function listFilterOptions(){
        $result = [];
        $filters = $this->group->filters ?? null;
        if ($filters) {
            foreach ($filters as $filter) {
                if ($filter->status == 1){
                    $options = FilterOption::where('filter_id', $filter->id)->get();
                    $result[$filter->name] = $options->pluck('option');
                }
            }
        }
        return $result;
    }


    public function calculateFinalPrice($options = [])
    {
        $color = $options['color'] ?? null;
        $size = $options['size'] ?? null;
        $brand = $options['brand'] ?? null;
        $selectedPrice = $options['selectedPrice'] ?? 'cash'; // cash, credit, day_x
        $count = $options['count'] ?? 1;

        $basePrice = $this->price * $this->ratio;
        $final = $basePrice;

        // ---------- Color ----------
        if ($color) {
            $selected = $this->colors->where('color', $color)->first();
            if ($selected) {
                $final += $selected->price * $this->ratio;
            }
        }

        // ---------- Size ----------
        if ($size) {
            $selected = $this->sizes->where('size', $size)->first();
            if ($selected) {
                $final += $selected->price * $this->ratio;
            }
        }

        // ---------- Brand ----------
        if ($brand) {
            $selected = $this->brands->where('name', $brand)->first();
            if ($selected) {
                $final += $selected->price * $this->ratio;
            }
        }

        // ---------- USER ----------
        $user = auth()->user();
        if ($user) {
            $category = $user->category;

            // credit price
            if ($selectedPrice === 'credit') {
                $final += ($final * $category->percent) / 100;
            }

            // check price (day_30, day_45 ...)
            if (str_starts_with($selectedPrice, 'day_')) {
                $day = (int) str_replace('day_', '', $selectedPrice);
                $rule = $category->checkRules->where('term_days', $day)->first();
                if ($rule) {
                    $final += ($final * $rule->percent) / 100;
                }
            }
        }

        // ---------- COUNT ----------
        return $final * $count;
    }





}
