<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class CheckRules extends Model
{
    protected $table = "user_category_check_rules";
    protected $fillable = [
        'name',
        'category_user_id',
        'term_days',
        'percent',
    ];
    
}
