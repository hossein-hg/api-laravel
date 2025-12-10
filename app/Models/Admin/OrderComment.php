<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class OrderComment extends Model
{
    protected $fillable = ['user_id','role','order_id','description'];
}
