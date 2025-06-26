<?php

namespace App\Client\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'client_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'type' => 'string'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
