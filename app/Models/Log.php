<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'level',
        'message',
        'context',
        'trace',
        'file',
        'line',
        'user_id',
        'ip_address',
        'user_agent',
        'url'
    ];

    protected $casts = [
        'context' => 'array', 
    ];

    // رابطه با User (اگر authenticated باشه)
    public function user()
    {
        return $this->belongsTo(related: User::class);
    }

    // Scope برای فیلتر (مثل خطاهای اخیر)
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>', now()->subDays(7));
    }
}
