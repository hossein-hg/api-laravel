<?php

namespace App\Models\Admin;
use App\Models\Admin\Product;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity', 'price', 'ratio','discount','size','color')->withTimestamps();
    }

    protected $fillable = [
        'user_id',
        'count',
        'status',
        'total_price'
    ];
}
