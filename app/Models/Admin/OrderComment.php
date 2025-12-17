<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderComment extends Model
{
    protected $fillable = ['user_id','role','order_id','description'];
    public function user(){
        return $this->belongsTo(User::class);
    }
    
}
