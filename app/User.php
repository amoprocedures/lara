<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

// JWT contract
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'dim_user';

    protected $fillable = ['user_name', 'email', 'password'];

    protected $hidden = ['password', 'secret_code', 'secret_code_sent_at'];

    protected $casts = ['email_verified_at' => 'datetime', 'id' => 'string'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


}
