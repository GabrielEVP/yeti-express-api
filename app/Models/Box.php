<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Box extends Model
{
    use HasFactory;

    protected $table = 'box';

    protected $fillable = [
        'user_id',
        'open_time',
        'close_time'
    ];

    public $timestamps = false;

    protected $casts = [
        'open_time' => 'datetime',
        'close_time' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function openDeliveries()
    {
        return $this->hasMany(Delivery::class, 'open_box_id');
    }

    public function closeDeliveries()
    {
        return $this->hasMany(Delivery::class, 'close_box_id');
    }
}