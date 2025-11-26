<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    protected $fillable  = [
        'category_user_id',
        'user_id',
        'term_days',
        'image',
        'clearing_date',
        'staus',
    ]; 
}
