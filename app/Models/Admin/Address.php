<?php

namespace App\Models\Admin;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    public function user(){
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
        'user_id',
        'province',
        'city',
        'phone',
        'address',
        'code',
        'first_name',
        'last_name',
        'mobile',
        'description',
        'email',
        'status',
    ] ;

    
}
