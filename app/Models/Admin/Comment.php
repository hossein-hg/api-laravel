<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Comment extends Model
{
    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
