<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class Employer extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'user_id',
    ];

    public $timestamps = false;

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'active' => 'boolean',
        'role' => 'string',
        'created_at' => 'datetime',
        'password' => 'hashed'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}