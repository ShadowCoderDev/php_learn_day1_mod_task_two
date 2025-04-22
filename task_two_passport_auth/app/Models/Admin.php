<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email', 
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}