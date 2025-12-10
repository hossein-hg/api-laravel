<?php

namespace App\Models\Admin;
use App\Models\Admin\Filter;
use App\Models\Admin\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    public function filters(){
        return $this->hasMany(Filter::class);
    }
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    protected $fillable = [
        'name',
        'level',
        'parent',
        'status',
        'url',
        'image',
    ];


}
