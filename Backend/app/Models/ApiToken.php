<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'token',
    ];

    protected $hidden = [
        'token',
    ];
}