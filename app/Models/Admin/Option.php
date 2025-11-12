<?php

namespace App\Models\Admin;
use App\Models\Admin\Filter;
use App\Models\Admin\Product;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    public function filter()
    {
        return $this->belongsTo(Filter::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_option');
    }
}
