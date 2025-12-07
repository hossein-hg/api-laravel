<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Admin\Address;
use App\Models\Admin\Check;
use App\Models\Admin\UserCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'phone',
        'category_id',
        'telephone',
        'avatar',
        'company_name',
        'national_code',
        'economic_code',
        'registration_number',   
        'is_active'
    ];
    use HasRoles;
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'roles',
        'category',
        
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function checkes(){
        return $this->hasMany(Check::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

   
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->getRoleNames()->first() ?? 'user',  // role اول (یا پیش‌فرض 'user')
            // می‌تونی claims دیگه‌ای مثل 'permissions' یا 'email_verified' هم اضافه کنی
        ];
    }

    public function addresses(){
        return $this->hasMany(Address::class);
    }

    public function category(){
        return $this->belongsTo(UserCategory::class,'category_id');
    }
}
