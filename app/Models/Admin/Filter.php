<?php

namespace App\Models\Admin;
use App\Models\Admin\Option;
use App\Models\Admin\Group;
use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    public function options(){
        return $this->hasMany(Option::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
