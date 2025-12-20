<?php

namespace App\Models\Admin;
use App\Models\User;
use App\Models\Admin\Product;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity','warranty','price','inventory','color','size','ratio','product_price','pay_type','brand')->withTimestamps();
    }

    protected $fillable = ['total_price','count','user_id'];
}

