<?php

namespace App\Models\Admin;
use App\Models\Admin\Product;
use App\Models\Admin\Check;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity', 'price', 'ratio','discount','size','color','brand','pay_type','product_price','init_price','inventory')->withTimestamps();
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function checkes(){ 
        return $this->hasMany(Check::class);
     }

    protected $fillable = [
        'user_id',
        'count',
        'status',
        'total_price',
        'cart_id',
        'description'
    ];
}
