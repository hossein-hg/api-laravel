<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class UserCategory extends Model
{
    public function checkRules(){
        return $this->hasMany(CheckRules::class,'category_user_id');
    }

    protected $fillable = [
        'name',
        'user_id',
        'max_credit',
        'percent',
    ] ;
}
