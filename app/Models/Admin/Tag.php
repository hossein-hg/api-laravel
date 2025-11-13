<?php

namespace App\Models\Admin;
use App\Models\Admin\Product;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public function products()
    {
        return $this->belongsToMany(
            \App\Models\Admin\Product::class,  // FQN کامل Product (بدون import – حل circular)
            'product_tag',  // pivot table name
            'tag_id',       // local key (از tags)
            'product_id'    // foreign key (از products)
        )->withTimestamps();
    }
}
