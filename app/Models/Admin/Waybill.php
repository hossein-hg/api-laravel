<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Waybill extends Model
{
    protected $fillable = [
        'code',
        'name',
        'mobile',
        'plate',
        'description',
        'order_id',
    ];
}
