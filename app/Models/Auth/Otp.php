<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class Otp extends Model
{
         protected $fillable = ['phone','code','name','gender','expires_at','attemps'];
    protected $dates = ['expires_at'];
    public function isExpired()
    {
        return strtotime($this->expires_at) < time();
    }

    public function maxAttemptsReached($max = 5)
    {
        return $this->attempts >= $max;
    }

    public function incrementAttempts()
    {
        $this->attempts++;
        $this->save();
    }

    

}
