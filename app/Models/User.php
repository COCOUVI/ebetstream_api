<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'username',
        'email',
        'phone',
        'password',
        'promo_code',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relation avec le wallet
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Relation avec le profil
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}
